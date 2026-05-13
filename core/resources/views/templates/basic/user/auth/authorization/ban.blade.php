@extends($activeTemplate .'layouts.frontend')
@section('content')
<section class="py-120">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="text-end">
                <a href="{{ route('home') }}" class="fw-bold home-link"> <i class="las la-long-arrow-alt-left"></i> @lang('Home')</a>
            </div>
            <div class="card custom--card text-center">
                <div class="card-body">
                    <h2 class="text-center text--danger">@lang('YOU ARE BANNED')</h2>
                    <p class="fw-bold mb-1">@lang('Reason'):</p>
                    <p>{{ $user->ban_reason }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection



