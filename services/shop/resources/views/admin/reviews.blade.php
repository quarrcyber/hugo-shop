@extends('layouts.app')
@section('title', 'Kiểm duyệt đánh giá · Hugo Shop')
@section('content')
<div class="page shell"><header class="page-head"><h1 class="page-title">Đánh giá</h1><p class="page-lede">Duyệt hoặc từ chối nội dung từ khách đã mua hàng.</p></header>@include('admin._nav')<div class="stack">@foreach($reviews as $review)<article class="alert"><p><strong>{{ $review->product->name }}</strong> · {{ $review->user->name }} · {{ $review->rating }}/5</p><p>{{ $review->content }}</p><form class="cluster" method="post" action="{{ route('admin.reviews.update', $review) }}">@csrf @method('patch')<select class="field__control" name="status"><option value="published">Duyệt</option><option value="rejected">Từ chối</option></select><button class="btn btn--quiet" type="submit">Cập nhật</button></form></article>@endforeach</div><div class="pagination">{{ $reviews->links() }}</div></div>
@endsection
