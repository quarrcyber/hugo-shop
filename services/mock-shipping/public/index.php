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
    $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
};

$app->add(function (Request $request, $handler) use ($json): Response {
    if ($request->getUri()->getPath() === '/health') {
        return $handler->handle($request);
    }

    $provided = preg_replace('/^Bearer\s+/i', '', $request->getHeaderLine('Authorization'));
    if (! hash_equals((string) getenv('SERVICE_KEY'), $provided)) {
        return $json(new \Slim\Psr7\Response(), ['message' => 'Unauthorized'], 401);
    }

    return $handler->handle($request);
});

$app->get('/health', fn (Request $request, Response $response) => $json($response, ['status' => 'ok']));

$app->post('/quote', function (Request $request, Response $response) use ($json): Response {
    $body = (array) $request->getParsedBody();
    $district = trim((string) ($body['district'] ?? ''));
    $weight = max(1, (int) ($body['weight_grams'] ?? 1));
    if ($district === '') {
        return $json($response, ['message' => 'District is required'], 422);
    }

    return $json($response, [
        'service' => $weight > 5000 ? 'standard' : 'express',
        'fee' => 22000 + (int) ceil($weight / 1000) * 3000,
        'estimated_days' => $weight > 5000 ? 4 : 2,
    ]);
});

$app->post('/shipments', function (Request $request, Response $response) use ($json): Response {
    $body = (array) $request->getParsedBody();
    if (empty($body['order_number']) || empty($body['address'])) {
        return $json($response, ['message' => 'Order number and address are required'], 422);
    }

    return $json($response, [
        'tracking_code' => 'HUGO'.strtoupper(bin2hex(random_bytes(5))),
        'status' => 'created',
        'order_number' => $body['order_number'],
    ], 201);
});

$app->get('/shipments/{code}', function (Request $request, Response $response, array $args) use ($json): Response {
    if (! preg_match('/^HUGO[A-F0-9]{10}$/', (string) $args['code'])) {
        return $json($response, ['message' => 'Shipment not found'], 404);
    }

    return $json($response, ['tracking_code' => $args['code'], 'status' => 'in_transit', 'updated_at' => gmdate(DATE_ATOM)]);
});

$app->run();
