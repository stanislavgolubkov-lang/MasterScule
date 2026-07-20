@php
    $selectedBrands = $selectedBrands ?? [];
    $viewMode = $viewMode ?? 'grid';
    $resetParameters = array_filter([
        'category' => $activeCategory?->slug,
        'task' => request('task'),
    ]);
    $resetUrl = route('catalog', $resetParameters);
@endphp

<form method="get" class="catalog-filter-form">
    @if(request('task'))<input type="hidden" name="task" value="{{ request('task') }}">@endif
    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
    @if($viewMode)<input type="hidden" name="view" value="{{ $viewMode }}">@endif
    <label>{{ __('ui.brand') }}</label>
    <div class="filter-checks">
        @foreach($brands as $brand)
            <label><input type="checkbox" name="brand[]" value="{{ $brand->slug }}" @checked(in_array($brand->slug, $selectedBrands, true))> {{ $brand->name }}</label>
        @endforeach
    </div>
    <label>{{ __('ui.price') }}</label>
    <div class="price-filter">
        <input type="number" min="{{ $priceBounds['min'] ?? 0 }}" max="{{ $priceBounds['max'] ?? 0 }}" name="price_min" value="{{ request('price_min') }}" placeholder="{{ $priceBounds['min'] ?? 0 }}">
        <input type="number" min="{{ $priceBounds['min'] ?? 0 }}" max="{{ $priceBounds['max'] ?? 0 }}" name="price_max" value="{{ request('price_max') }}" placeholder="{{ $priceBounds['max'] ?? 0 }}">
    </div>
    <label class="check-line"><input type="checkbox" name="in_stock" value="1" @checked(request('in_stock'))> {{ __('ui.in_stock') }}</label>
    <label class="check-line"><input type="checkbox" name="discounted" value="1" @checked(request('discounted'))> {{ __('ui.promotions') }}</label>
    <div class="filter-actions">
        <button class="btn small">{{ __('ui.apply_filters') }}</button>
        <a class="btn small outline" href="{{ $resetUrl }}">{{ __('ui.reset') }}</a>
    </div>
</form>
