@props(['compact' => false])

<section {{ $attributes->class(['consultation-cta', 'consultation-cta-compact' => $compact]) }}>
    <div class="consultation-copy">
        <span>{{ __('ui.consultation_badge') }}</span>
        <h2>{{ __('ui.consultation_cta_title') }}</h2>
        <p>{{ __('ui.consultation_cta_text') }}</p>
    </div>
    <div class="consultation-actions">
        <a class="btn" href="tel:{{ config('store.phone_href') }}">{{ __('ui.consultation_call') }}</a>
        <a class="btn outline" href="mailto:{{ config('store.email') }}">{{ __('ui.consultation_write') }}</a>
        <a class="btn orange-btn" href="{{ route('page', 'contacts') }}">{{ __('ui.consultation_contacts') }}</a>
    </div>
</section>
