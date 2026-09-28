<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavedCardRequest;
use App\Models\CreditCard;
use App\Models\Order;
use App\Models\Product;
use App\Models\Wishlist;
use App\Modules\Orders\OrderCancellationService;
use App\Modules\Payments\PaymentClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.index', [
            'user' => $request->user()->load(['addresses', 'cards']),
            'orders' => $request->user()->orders()->with('items')->latest()->paginate(8),
            'wishlist' => Product::query()->whereIn('id', Wishlist::query()->where('user_id', $request->user()->id)->pluck('product_id'))->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'phone' => ['nullable', 'regex:/^0\d{9}$/']]);
        $request->user()->update($data);

        return back()->with('success', 'Thông tin cá nhân đã được cập nhật.');
    }

    public function order(Request $request, Order $order): View
    {
        $this->authorize('view', $order);

        return view('account.order', ['order' => $order->load(['items', 'payments'])]);
    }

    public function toggleWishlist(Request $request, Product $product): RedirectResponse
    {
        $wishlist = Wishlist::query()->where(['user_id' => $request->user()->id, 'product_id' => $product->id]);
        $wishlist->exists() ? $wishlist->delete() : Wishlist::query()->create(['user_id' => $request->user()->id, 'product_id' => $product->id]);

        return back()->with('success', 'Danh sách yêu thích đã được cập nhật.');
    }

    public function cancelOrder(Request $request, Order $order, OrderCancellationService $cancellations): RedirectResponse
    {
        $this->authorize('cancel', $order);
        $cancellations->cancel($order, $request->user(), $request->ip(), $request->userAgent());

        return back()->with('success', 'Đơn hàng đã được hủy và tồn kho đã được hoàn lại.');
    }

    public function storeCard(SavedCardRequest $request, PaymentClient $payments): RedirectResponse
    {
        try {
            $tokenized = $payments->tokenize(
                (string) $request->validated('card_number'),
                (string) $request->validated('expiration'),
                (string) $request->validated('cvv'),
            );
        } catch (Throwable) {
            throw ValidationException::withMessages(['card_number' => 'Không thể xác minh thẻ test. Vui lòng kiểm tra lại thông tin.']);
        }

        CreditCard::query()->create([
            'user_id' => $request->user()->id,
            'card_type' => $tokenized['card_type'],
            'last_four' => $tokenized['last_four'],
            'card_token' => $tokenized['token'],
            'expiration' => $tokenized['expiration'],
            'is_default' => ! $request->user()->cards()->exists(),
        ]);

        return back()->with('success', 'Phương thức thanh toán test đã được thêm.');
    }

    public function updateCard(Request $request, CreditCard $card): RedirectResponse
    {
        abort_unless($card->user_id === $request->user()->id, 403);
        $request->validate(['is_default' => ['required', 'accepted']]);

        DB::transaction(function () use ($request, $card): void {
            $request->user()->cards()->update(['is_default' => false]);
            $card->update(['is_default' => true]);
        });

        return back()->with('success', 'Thẻ mặc định đã được cập nhật.');
    }

    public function deleteCard(Request $request, CreditCard $card): RedirectResponse
    {
        abort_unless($card->user_id === $request->user()->id, 403);
        DB::transaction(function () use ($request, $card): void {
            $wasDefault = $card->is_default;
            $card->delete();
            if ($wasDefault) {
                $request->user()->cards()->latest()->first()?->update(['is_default' => true]);
            }
        });

        return back()->with('success', 'Phương thức thanh toán đã được xóa.');
    }
}
