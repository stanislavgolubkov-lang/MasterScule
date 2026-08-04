@extends('layouts.app')

@section('title', __('ui.login_title').' — '.config('store.domain_label'))

@section('content')
<section class="shell auth-card">
    <div>
        <h1>{{ __('ui.login_title') }}</h1>
        <p>{{ __('ui.login_text') }}</p>
    </div>
    <form method="post" action="{{ route('login.store') }}">
        @csrf
        @if($errors->any())
            <div class="form-errors" role="alert">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        <label>Email<input name="email" type="email" value="{{ old('email') }}" required></label>
        <label>{{ __('ui.password') }}<input name="password" type="password" required></label>
        <label class="check"><input type="checkbox" name="remember"> {{ __('ui.remember_me') }}</label>
        <button class="btn">{{ __('ui.login_button') }}</button>
        <a href="{{ route('register') }}">{{ __('ui.create_account') }}</a>
    </form>
</section>
@endsection
