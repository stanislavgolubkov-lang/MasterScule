@extends('layouts.app')

@section('content')
<section class="shell auth-card">
    <div>
        <h1>{{ __('ui.login_title') }}</h1>
        <p>{{ __('ui.login_text') }}</p>
    </div>
    <form method="post" action="{{ route('login.store') }}">
        @csrf
        <label>Email<input name="email" type="email" value="{{ old('email') }}" required></label>
        <label>{{ __('ui.password') }}<input name="password" type="password" required></label>
        <label class="check"><input type="checkbox" name="remember"> {{ __('ui.remember_me') }}</label>
        <button class="btn">{{ __('ui.login_button') }}</button>
        <a href="{{ route('register') }}">{{ __('ui.create_account') }}</a>
    </form>
</section>
@endsection
