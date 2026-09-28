<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Product;
use App\Models\Review;
use App\Modules\Reviews\ReviewService;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Product $product, ReviewService $reviews): RedirectResponse
    {
        $reviews->create($request->user(), $product, $request->validated());

        return back()->with('success', 'Đánh giá đã được gửi để kiểm duyệt.');
    }

    public function update(ReviewRequest $request, Review $review, ReviewService $reviews): RedirectResponse
    {
        $this->authorize('update', $review);
        $reviews->update($review, $request->validated());

        return back()->with('success', 'Đánh giá đã được cập nhật.');
    }

    public function destroy(Review $review, ReviewService $reviews): RedirectResponse
    {
        $this->authorize('delete', $review);
        $reviews->delete($review);

        return back()->with('success', 'Đánh giá đã được xóa.');
    }
}
