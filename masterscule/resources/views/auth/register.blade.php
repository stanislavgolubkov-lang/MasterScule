@extends('layouts.app')

@section('content')
<section class="shell auth-card">
    <div>
        <h1>{{ __('ui.register_title') }}</h1>
        <p>{{ __('ui.register_text') }}</p>
    </div>
    <form method="post" action="{{ route('register.store') }}">
        @csrf
        <label>{{ __('ui.name') }}<input name="name" value="{{ old('name') }}" required></label>
        <label>Email<input name="email" type="email" value="{{ old('email') }}" required></label>
        <label>{{ __('ui.phone') }}<input name="phone" value="{{ old('phone') }}"></label>
        <label>{{ __('ui.password') }}<input name="password" type="password" required></label>
        <label>{{ __('ui.confirm_password') }}<input name="password_confirmation" type="password" required></label>
        <button class="btn">{{ __('ui.create_account') }}</button>
        <a href="{{ route('login') }}">{{ __('ui.already_have_account') }}</a>
    </form>
</section>
@endsection
