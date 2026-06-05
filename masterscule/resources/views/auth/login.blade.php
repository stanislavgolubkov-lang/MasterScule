@extends('layouts.app')

@section('content')
<section class="shell auth-card">
    <div>
        <h1>Autentificare</h1>
        <p>Pentru finalizarea comenzii și urmărirea livrării este necesar să te autentifici sau să creezi un cont.</p>
    </div>
    <form method="post" action="{{ route('login.store') }}">
        @csrf
        <label>Email<input name="email" type="email" value="{{ old('email') }}" required></label>
        <label>Parolă<input name="password" type="password" required></label>
        <label class="check"><input type="checkbox" name="remember"> Ține-mă minte</label>
        <button class="btn">Intră în cont</button>
        <a href="{{ route('register') }}">Creează cont nou</a>
    </form>
</section>
@endsection
