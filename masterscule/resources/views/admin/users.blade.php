@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>Admin / Utilizatori</p><h1>Admin si utilizator</h1></section>
<section class="shell panel">
    <table>
        <tr><th>Nume</th><th>Email</th><th>Rol</th><th>Telefon</th><th>Companie</th><th>Status</th></tr>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td><strong>{{ $user->role }}</strong></td>
                <td>{{ $user->phone }}</td>
                <td>{{ $user->company_name }}</td>
                <td>{{ $user->status }}</td>
            </tr>
        @endforeach
    </table>
    {{ $users->links() }}
</section>
@endsection
