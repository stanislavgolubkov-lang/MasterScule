@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>{{ __('ui.admin') }}</p><h1>{{ __('ui.admin_panel') }}</h1></section>
<section class="shell account-main">
    <div class="stats">
        <div><strong>{{ $productsCount }}</strong><span>{{ __('ui.products') }}</span></div>
        <div><strong>{{ $ordersCount }}</strong><span>{{ __('ui.orders') }}</span></div>
        <div><strong>{{ $brandsCount }}</strong><span>{{ __('ui.brands') }}</span></div>
        <div><strong>{{ $usersCount }}</strong><span>{{ __('ui.users') }}</span></div>
    </div>
    <div class="admin-actions">
        <a class="btn" href="{{ route('admin.products') }}">CRUD {{ __('ui.products') }}</a>
        <a class="btn outline" href="{{ route('admin.parser.index') }}">{{ __('ui.parser_products') }}</a>
        <a class="btn outline" href="{{ route('admin.orders') }}">{{ __('ui.orders') }}</a>
        <a class="btn outline" href="{{ route('admin.users') }}">{{ __('ui.users') }}</a>
        @if(config('features.ai_assistant'))
            <a class="btn outline" href="{{ route('ai.advisor') }}" data-ai-open data-ai-prefill="{{ app()->isLocale('ru') ? 'Что может делать администратор в '.config('store.domain_label').'?' : 'Ce poate face administratorul in '.config('store.domain_label').'?' }}">AI Tools</a>
        @endif
    </div>
</section>
@endsection
