@extends('layouts.app')

@section('content')
<section class="shell auth-card admin-auth-card">
    <div>
        <h1>{{ __('ui.admin_login_title') }}</h1>
        <p>{{ __('ui.admin_login_text') }}</p>
    </div>
    <form method="post" action="{{ route('admin.login.store') }}">
        @csrf
        @if($errors->any())
            <div class="form-errors" role="alert">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        <label>Email<input name="email" type="email" value="{{ old('email') }}" required autofocus></label>
        <label>{{ __('ui.password') }}<input name="password" type="password" required></label>
        <label class="check"><input type="checkbox" name="remember"> {{ __('ui.remember_me') }}</label>
        <button class="btn">{{ __('ui.admin_login_button') }}</button>
        <a href="{{ route('home') }}">{{ __('ui.back_to_home') }}</a>
    </form>
</section>
@endsection
