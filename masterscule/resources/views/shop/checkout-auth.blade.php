@extends('layouts.app')

@section('content')
<section class="shell auth-card">
    <div>
        <h1>Finalizează comanda</h1>
        <p>Pentru finalizarea comenzii și urmărirea livrării este necesar să te autentifici sau să creezi un cont.</p>
        <div class="actions"><a class="btn" href="{{ route('login') }}">Autentificare</a><a class="btn outline" href="{{ route('register') }}">Înregistrare</a></div>
    </div>
</section>
@endsection
