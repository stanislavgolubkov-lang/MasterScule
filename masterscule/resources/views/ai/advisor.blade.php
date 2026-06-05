@extends('layouts.app')

@section('content')
<section class="shell ai-page">
    <div class="panel ai-panel">
        <span class="ai-panel-kicker">AI MasterScule</span>
        <h1>Consultant AI pentru scule</h1>
        <p>Descrie lucrarea, bugetul sau actiunea de pe site. AI-ul raspunde cu pasi clari si produse reale din catalog.</p>
        <form method="post" action="{{ route('ai.ask') }}">
            @csrf
            <label>Descrie lucrarea<textarea name="prompt" required placeholder="Ex: am nevoie de un set pentru garaj pana la 3000 RON">{{ old('prompt') }}</textarea></label>
            <div class="ai-prompts">
                @foreach($quickPrompts as $prompt)
                    <button type="button" data-ai-prompt="{{ $prompt }}">{{ $prompt }}</button>
                @endforeach
            </div>
            <button class="btn">Alege scula potrivita</button>
        </form>
        @if(session('ai_response'))
            <pre class="ai-response">{{ session('ai_response') }}</pre>
        @endif
    </div>
    <div>
        <div class="section-head ai-products-head">
            <h2>{{ $responseProducts->isNotEmpty() ? 'Recomandari pentru cererea ta' : 'Produse recomandate' }}</h2>
        </div>
        <div class="product-grid">
            @foreach(($responseProducts->isNotEmpty() ? $responseProducts : $recommendations) as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </div>
</section>
@endsection
