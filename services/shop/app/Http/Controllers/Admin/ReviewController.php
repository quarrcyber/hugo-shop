<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Modules\Reviews\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        return view('admin.reviews', ['reviews' => Review::query()->with(['user', 'product'])->latest()->paginate(25)]);
    }

    public function update(Request $request, Review $review, ReviewService $reviews): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['published', 'rejected'])]]);
        $reviews->moderate($review, $request->user(), $data['status']);

        return back()->with('success', 'Đánh giá đã được kiểm duyệt.');
    }
}
