<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require dirname(__DIR__).'/vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(false, true, true);

$json = static function (Response $response, array $payload, int $status = 200): Response {
    $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
};

$app->add(function (Request $request, $handler) use ($json): Response {
    if ($request->getUri()->getPath() === '/health') {
        return $handler->handle($request);
    }

    $provided = preg_replace('/^Bearer\s+/i', '', $request->getHeaderLine('Authorization'));
    $expected = (string) getenv('SERVICE_KEY');
    if ($expected === '' || ! hash_equals($expected, $provided)) {
        return $json(new \Slim\Psr7\Response(), ['message' => 'Unauthorized'], 401);
    }

    return $handler->handle($request);
});

$app->get('/health', fn (Request $request, Response $response) => $json($response, ['status' => 'ok']));

$app->post('/tokenize', function (Request $request, Response $response) use ($json): Response {
    $body = (array) $request->getParsedBody();
    $number = preg_replace('/\D+/', '', (string) ($body['card_number'] ?? ''));
    $expiry = (string) ($body['expiration'] ?? '');

    if (! in_array($number, ['4242424242424242', '4000000000000002', '4000000000003220'], true)
        || ! preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)
        || ! preg_match('/^\d{3,4}$/', (string) ($body['cvv'] ?? ''))) {
        return $json($response, ['message' => 'Invalid test card details'], 422);
    }

    $scenario = match ($number) {
        '4000000000000002' => 'decline',
        '4000000000003220' => 'auth',
        default => 'ok',
    };

    return $json($response, [
        'token' => sprintf('tok_%s_%s', $scenario, bin2hex(random_bytes(12))),
        'card_type' => 'visa',
        'last_four' => substr($number, -4),
        'expiration' => $expiry,
    ], 201);
});

$app->post('/charge', function (Request $request, Response $response) use ($json): Response {
    $body = (array) $request->getParsedBody();
    $idempotency = trim($request->getHeaderLine('Idempotency-Key'));
    $token = (string) ($body['token'] ?? '');
    $amount = (int) ($body['amount'] ?? 0);

    if ($idempotency === '' || $amount < 1000 || ! str_starts_with($token, 'tok_')) {
        return $json($response, ['message' => 'Invalid charge request'], 422);
    }

    $cacheFile = sys_get_temp_dir().'/hugo-pay-'.hash('sha256', $idempotency).'.json';
    if (is_file($cacheFile)) {
        return $json($response, json_decode((string) file_get_contents($cacheFile), true, 512, JSON_THROW_ON_ERROR));
    }

    $status = str_contains($token, '_decline_') ? 'failed' : (str_contains($token, '_auth_') ? 'requires_action' : 'succeeded');
    $result = [
        'transaction_id' => 'txn_'.bin2hex(random_bytes(10)),
        'idempotency_key' => $idempotency,
        'status' => $status,
        'amount' => $amount,
        'currency' => 'VND',
    ];
    file_put_contents($cacheFile, json_encode($result, JSON_THROW_ON_ERROR), LOCK_EX);

    $webhookUrl = (string) getenv('SHOP_WEBHOOK_URL');
    $secret = (string) getenv('WEBHOOK_SECRET');
    if ($webhookUrl !== '' && $secret !== '' && $status !== 'requires_action') {
        $event = json_encode(['type' => 'payment.updated', 'data' => $result], JSON_THROW_ON_ERROR);
        $curl = curl_init($webhookUrl);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $event,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Hugo-Signature: '.hash_hmac('sha256', $event, $secret)],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
        ]);
        curl_exec($curl);
        curl_close($curl);
    }

    return $json($response, $result, $status === 'failed' ? 402 : 200);
});

$app->post('/refund', function (Request $request, Response $response) use ($json): Response {
    $body = (array) $request->getParsedBody();
    if (! preg_match('/^txn_[a-f0-9]{20}$/', (string) ($body['transaction_id'] ?? ''))) {
        return $json($response, ['message' => 'Unknown transaction'], 422);
    }

    return $json($response, [
        'refund_id' => 'ref_'.bin2hex(random_bytes(10)),
        'transaction_id' => $body['transaction_id'],
        'status' => 'refunded',
    ]);
});

$app->run();
