@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>Admin / Comenzi</p><h1>Gestionare comenzi</h1></section>
<section class="shell panel"><table><tr><th>Comandă</th><th>Client</th><th>Status</th><th>Total</th><th>Data</th></tr>@foreach($orders as $order)<tr><td>{{ $order->order_number }}</td><td>{{ $order->customer_name }}</td><td>{{ $order->status }}</td><td>{{ number_format((float)$order->total, 2, ',', '.') }} RON</td><td>{{ $order->created_at->format('d.m.Y') }}</td></tr>@endforeach</table>{{ $orders->links() }}</section>
@endsection
