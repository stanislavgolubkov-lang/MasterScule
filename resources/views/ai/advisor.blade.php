@extends('layouts.app')

@section('content')
<section class="shell ai-page">
    <div class="panel ai-panel">
        <span class="ai-panel-kicker">{{ __('ui.ai_kicker') }}</span>
        <h1>{{ __('ui.ai_page_title') }}</h1>
        <p>{{ __('ui.ai_page_text') }}</p>
        <form method="post" action="{{ route('ai.ask') }}">
            @csrf
            <label>{{ __('ui.describe_work') }}<textarea name="prompt" required placeholder="{{ __('ui.ai_placeholder') }}">{{ old('prompt') }}</textarea></label>
            <div class="ai-prompts">
                @foreach($quickPrompts as $prompt)
                    <button type="button" data-ai-prompt="{{ $prompt }}">{{ $prompt }}</button>
                @endforeach
            </div>
            <button class="btn">{{ __('ui.choose_tool') }}</button>
        </form>
        @if(session('ai_response'))
            <pre class="ai-response">{{ session('ai_response') }}</pre>
        @endif
    </div>
    <div>
        <div class="section-head ai-products-head">
            <h2>{{ $responseProducts->isNotEmpty() ? __('ui.ai_recommendations') : __('ui.recommended_products') }}</h2>
        </div>
        <div class="product-grid">
            @foreach(($responseProducts->isNotEmpty() ? $responseProducts : $recommendations) as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </div>
</section>
@endsection
