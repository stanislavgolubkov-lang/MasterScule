@extends('layouts.app')

@section('title', $page->title.' | MasterScule.ro')

@section('content')
<section class="shell page-title"><p>MasterScule.ro</p><h1>{{ $page->title }}</h1></section>
<section class="shell panel legal"><p>{{ $page->content }}</p></section>
@endsection
