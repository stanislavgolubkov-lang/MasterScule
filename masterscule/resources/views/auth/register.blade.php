@extends('layouts.app')

@section('content')
<section class="shell auth-card">
    <div>
        <h1>Înregistrare</h1>
        <p>Contul păstrează comenzile, adresele și recomandările pentru atelierul tău.</p>
    </div>
    <form method="post" action="{{ route('register.store') }}">
        @csrf
        <label>Nume<input name="name" value="{{ old('name') }}" required></label>
        <label>Email<input name="email" type="email" value="{{ old('email') }}" required></label>
        <label>Telefon<input name="phone" value="{{ old('phone') }}"></label>
        <label>Parolă<input name="password" type="password" required></label>
        <label>Confirmă parola<input name="password_confirmation" type="password" required></label>
        <button class="btn">Creează cont</button>
        <a href="{{ route('login') }}">Am deja cont</a>
    </form>
</section>
@endsection
