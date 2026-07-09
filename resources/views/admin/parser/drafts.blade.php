@extends('layouts.admin')

@section('content')
@php($ru = app()->isLocale('ru'))

<section class="shell page-title">
    <p>{{ __('ui.admin') }} / <a href="{{ route('admin.parser.index') }}">{{ __('ui.parser_products') }}</a></p>
    <h1>{{ $ru ? 'Черновики из прайсов' : 'Drafturi din liste de preturi' }}</h1>
    <span>{{ $ru ? 'Все товары, подготовленные парсером, остаются draft до ручного утверждения.' : 'Toate produsele pregatite de parser raman draft pana la aprobarea manuala.' }}</span>
</section>

<section class="shell parser-tabs">
    <a href="{{ route('admin.parser.index') }}">{{ $ru ? 'Импорт прайс-листа' : 'Import lista preturi' }}</a>
    <a class="active" href="{{ route('admin.parser.drafts') }}">{{ $ru ? 'Черновики из прайсов' : 'Drafturi din liste' }}</a>
    <a href="{{ route('admin.parser.rules') }}">{{ $ru ? 'Правила категорий' : 'Reguli categorii' }}</a>
</section>

<section class="shell panel">
    <div class="parser-table-wrap">
        <table class="parser-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>{{ __('ui.brand') }}</th>
                    <th>{{ $ru ? 'Название' : 'Denumire' }}</th>
                    <th>{{ __('ui.category') }}</th>
                    <th>{{ $ru ? 'Цена / остаток' : 'Pret / stoc' }}</th>
                    <th>{{ __('ui.status') }}</th>
                    <th>{{ __('ui.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td><strong>{{ $item->sku }}</strong></td>
                        <td>{{ $item->brand ?: 'Auto' }}</td>
                        <td>{{ $item->name_ru ?: $item->found_title }}</td>
                        <td>{{ $item->category?->display_name ?: ($ru ? 'Нужна проверка' : 'Necesita verificare') }}</td>
                        <td>{{ $item->parsed_price ?? '-' }} / {{ $item->parsed_stock ?? '-' }}</td>
                        <td><span class="parser-status parser-status-{{ $item->status }}">{{ $item->createdProduct?->status ?: $item->status }}</span></td>
                        <td><a class="btn small" href="{{ route('admin.parser.items.show', $item) }}">{{ __('ui.open') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">{{ __('ui.collection_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $items->links() }}
</section>
@endsection
