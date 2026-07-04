@extends('layouts.app')

@php
    $translatedTitle = __("pages.{$page->slug}.title");
    $translatedContent = __("pages.{$page->slug}.content");
    $title = $translatedTitle === "pages.{$page->slug}.title" ? $page->title : $translatedTitle;
    $content = $translatedContent === "pages.{$page->slug}.content" ? $page->content : $translatedContent;
@endphp

@section('title', $title.' | '.config('store.domain_label'))

@section('content')
<section class="shell page-title"><p>{{ config('store.domain_label') }}</p><h1>{{ $title }}</h1></section>
<section class="shell panel legal"><p>{{ $content }}</p></section>
@endsection
