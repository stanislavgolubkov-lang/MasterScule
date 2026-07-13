@extends('layouts.admin')

@section('content')
<section class="shell page-title"><p>{{ __('ui.admin') }} / {{ __('ui.users') }}</p><h1>{{ app()->isLocale('ru') ? 'Администраторы и пользователи' : 'Admin si utilizator' }}</h1></section>
<section class="shell panel admin-table-panel">
    <div class="admin-table-scroll">
    <table>
        <tr><th>{{ __('ui.name') }}</th><th>Email</th><th>{{ app()->isLocale('ru') ? 'Роль' : 'Rol' }}</th><th>{{ __('ui.phone') }}</th><th>{{ __('ui.company_name') }}</th><th>{{ __('ui.status') }}</th></tr>
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
    </div>
    {{ $users->links() }}
</section>
@endsection
