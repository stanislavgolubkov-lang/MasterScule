@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>{{ config('store.domain_label') }}</p><h1>{{ $title }}</h1></section>
<section class="shell panel"><p>{{ __('ui.placeholder_text') }}</p></section>
@endsection
