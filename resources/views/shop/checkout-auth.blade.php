@extends('layouts.app')

@section('content')
<section class="shell auth-card">
    <div>
        <h1>{{ __('ui.checkout_title') }}</h1>
        <p>{{ __('ui.login_text') }}</p>
        <div class="actions"><a class="btn" href="{{ route('login') }}">{{ __('ui.login_title') }}</a><a class="btn outline" href="{{ route('register') }}">{{ __('ui.register_title') }}</a></div>
    </div>
</section>
@endsection
