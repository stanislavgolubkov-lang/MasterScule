<?php

namespace App\Http\Controllers;

use App\Models\AiRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AiController extends Controller
{
    public function advisor()
    {
        $responseProductIds = session('ai_product_ids', []);

        return view('ai.advisor', [
            'recommendations' => Product::with('brand')
                ->availableForSale()
                ->where('is_featured', true)
                ->where('main_image', 'not like', '%product-placeholder%')
                ->orderByDesc('is_bestseller')
                ->limit(4)
                ->get(),
            'responseProducts' => Product::with('brand')
                ->whereIn('id', $responseProductIds)
                ->availableForSale()
                ->where('main_image', 'not like', '%product-placeholder%')
                ->get(),
            'quickPrompts' => app()->isLocale('ru') ? [
                'Помоги выбрать набор инструментов для гаража до 2500 MDL',
                'Как добавить товар в корзину и оформить заказ?',
                'Что может делать администратор в панели MasterScule?',
                'Нужен пневмоинструмент M7 для автосервиса',
                'Объясни доставку, гарантию и возврат',
            ] : [
                'Ajuta-ma sa aleg un set de scule pentru garaj pana la 2500 MDL',
                'Cum adaug un produs in cos si finalizez comanda?',
                'Ce poate face un administrator in panoul MasterScule?',
                'Am nevoie de un pistol pneumatic M7 pentru service auto',
                'Explica livrarea, garantia si returul',
            ],
        ]);
    }

    public function ask(Request $request)
    {
        $data = $request->validate(['prompt' => ['required', 'string', 'max:1000']]);
        $prompt = Str::lower($data['prompt']);
        $products = $this->matchingProducts($prompt);
        $response = $this->buildResponse($prompt, $products);

        AiRequest::create([
            'user_id' => auth()->id(),
            'type' => 'advisor',
            'prompt' => $data['prompt'],
            'response' => $response,
            'status' => 'draft',
            'product_ids' => $products->pluck('id')->all(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'response' => $response,
                'products' => $products->map(fn ($product) => [
                    'name' => $product->display_name,
                    'sku' => $product->sku,
                    'price' => money($product->price),
                    'image' => $product->main_image,
                    'url' => route('product.show', $product->slug),
                    'brand' => $product->brand?->name,
                ])->values(),
            ]);
        }

        return back()->with([
            'ai_response' => $response,
            'ai_product_ids' => $products->pluck('id')->all(),
        ]);
    }

    private function matchingProducts(string $prompt): Collection
    {
        $budget = $this->budgetFromPrompt($prompt);
        $terms = collect(preg_split('/[\s,.;:!?()]+/u', $prompt))
            ->filter(fn ($term) => mb_strlen($term) >= 3)
            ->reject(fn ($term) => in_array($term, [
                'pentru', 'produs', 'produse', 'scule', 'service', 'garaj', 'atelier', 'cum', 'care',
                'vreau', 'caut', 'am', 'nevoie', 'site', 'instrument', 'instrumente', 'master', 'masterscule',
                'для', 'товар', 'товары', 'инструмент', 'инструменты', 'сервис', 'гараж', 'как', 'что',
                'нужно', 'нужен', 'нужна', 'ищу', 'сайт', 'мастер', 'masterscule',
            ], true))
            ->take(8)
            ->values();

        return Product::query()
            ->with(['brand', 'category'])
            ->availableForSale()
            ->where('main_image', 'not like', '%product-placeholder%')
            ->when(str_contains($prompt, 'king') || str_contains($prompt, 'tony'), fn ($query) => $query->whereHas('brand', fn ($brand) => $brand->where('slug', 'king-tony')))
            ->when(str_contains($prompt, 'm7') || str_contains($prompt, 'mighty') || str_contains($prompt, 'seven'), fn ($query) => $query->whereHas('brand', fn ($brand) => $brand->where('slug', 'm7-mighty-seven')))
            ->when($budget, fn ($query) => $query->where('price', '<=', $budget))
            ->where(function ($query) use ($prompt, $terms) {
                if (str_contains($prompt, 'pneumatic') || str_contains($prompt, 'aer') || str_contains($prompt, 'пневм')) {
                    $query->orWhereHas('category', fn ($category) => $category->where('slug', 'scule-pneumatice'));
                }

                if (str_contains($prompt, 'set') || str_contains($prompt, 'trusa') || str_contains($prompt, 'набор')) {
                    $query->orWhereHas('category', fn ($category) => $category->where('slug', 'seturi-de-scule'));
                }

                if (str_contains($prompt, 'dinamometric') || str_contains($prompt, 'динамометр')) {
                    $query->orWhereHas('category', fn ($category) => $category->where('slug', 'chei-dinamometrice'));
                }

                foreach ($terms as $term) {
                    $query
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhere('short_description', 'like', "%{$term}%");
                }
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('is_bestseller')
            ->orderBy('price')
            ->limit(4)
            ->get();
    }

    private function buildResponse(string $prompt, Collection $products): string
    {
        $actions = $this->matchedActions($prompt);
        $lines = [];

        $isRu = app()->isLocale('ru');

        $lines[] = $isRu
            ? 'Я знаю основные действия на '.config('store.domain_label').' и рекомендую только реальные товары из каталога.'
            : 'Cunosc actiunile principale '.config('store.domain_label').' si recomand doar produse reale din catalog.';

        if ($actions !== []) {
            $lines[] = $isRu ? "\nРекомендуемые шаги:" : "\nPasi recomandati:";
            foreach ($actions as $action) {
                $lines[] = '- '.$action;
            }
        }

        if ($products->isNotEmpty()) {
            $lines[] = $isRu ? "\nПодходящие товары из каталога:" : "\nProduse potrivite din catalog:";
            foreach ($products as $product) {
                $lines[] = '- '.$product->display_name.' | SKU '.$product->sku.' | '.money($product->price).' | '.route('product.show', $product->slug);
            }
        } else {
            $lines[] = $isRu
                ? "\nЯ не нашел точный товар по запросу. Уточните бренд, бюджет, задачу или код товара."
                : "\nNu am gasit un produs exact pentru cerere. Spune-mi brandul, bugetul, lucrarea sau codul produsului.";
        }

        $lines[] = $isRu
            ? "\nМогу помочь с поиском, фильтрами, выбором товара, корзиной, checkout, регистрацией, аккаунтом, избранным, сравнением, доставкой, возвратом, гарантией и администрированием."
            : "\nPot ajuta cu: cautare, filtrare, alegere produs, cos, checkout, inregistrare, cont, favorite, comparare, livrare, retur, garantie si administrare.";

        return implode("\n", $lines);
    }

    private function matchedActions(string $prompt): array
    {
        $dictionary = app()->isLocale('ru') ? [
            'catalog' => 'Откройте каталог: '.route('catalog').'. Используйте поиск, категории, бренд и фильтры.',
            'product' => 'На странице товара проверьте фото, код, цену, наличие, описание и характеристики. Затем нажмите "'.__('ui.add_to_cart').'" или "'.__('ui.buy_now').'".',
            'cart' => 'Корзина здесь: '.route('cart.index').'. Можно менять количество, удалять товары, применять промокод и переходить к оформлению.',
            'checkout' => 'Оформление заказа: '.route('checkout.show').'. Для завершения заказа нужен аккаунт или вход.',
            'account' => 'Аккаунт клиента: '.(auth()->check() ? route('account.dashboard') : route('login')).'. Здесь видны заказы, личные данные, адреса и избранное.',
            'admin' => 'Админ-панель: '.route('admin.dashboard').'. Администратор управляет товарами, заказами и пользователями.',
            'wishlist' => 'Избранное: '.route('wishlist').'. Клиент может сохранить товары и вернуться к ним позже.',
            'compare' => 'Сравнение: '.route('compare').'. Помогает сравнить близкие товары перед покупкой.',
            'promotions' => 'Акции: '.route('promotions').'. Здесь товары со скидками и активные предложения.',
            'new' => 'Новинки: '.route('new').'. Здесь товары, отмеченные как новые.',
            'bestsellers' => 'Топ продаж: '.route('bestsellers').'. Здесь популярные товары.',
            'delivery' => 'Доставка и оплата: '.route('page', 'delivery-payment').'. Условия доставки и доступные способы оплаты.',
            'warranty' => 'Гарантия: '.route('page', 'warranty').'. Базовый срок — 12 месяцев; условия нужно уточнять по каждому продукту.',
            'return' => 'Возврат: '.route('page', 'returns').'. Здесь условия возврата и компенсации.',
            'contact' => 'Контакты: '.route('page', 'contacts').'. Для сложного выбора можно обратиться к консультанту.',
        ] : [
            'catalog' => 'Deschide catalogul: '.route('catalog').'. Foloseste cautarea, categoriile, brandul si pretul pentru filtrare.',
            'product' => 'Pe pagina produsului verifica poza, codul, pretul, stocul, descrierea si specificatiile. Apoi apasa "Adauga in cos" sau "Cumpara acum".',
            'cart' => 'Cosul este aici: '.route('cart.index').'. Poti modifica cantitatea, sterge produse, aplica un cod promotional si continua spre checkout.',
            'checkout' => 'Checkout: '.route('checkout.show').'. Pentru finalizarea comenzii este necesar cont sau autentificare.',
            'account' => 'Cont client: '.(auth()->check() ? route('account.dashboard') : route('login')).'. Clientul vede comenzile, datele personale, adresele si favoritele.',
            'admin' => 'Panou admin: '.route('admin.dashboard').'. Administratorul gestioneaza produse, comenzi si utilizatori.',
            'wishlist' => 'Favorite: '.route('wishlist').'. Clientul poate salva produsele urmarite si reveni la ele mai tarziu.',
            'compare' => 'Comparare: '.route('compare').'. Clientul poate compara produse apropiate inainte de cumparare.',
            'promotions' => 'Promotii: '.route('promotions').'. Aici apar produsele cu reducere si oferte active.',
            'new' => 'Noutati: '.route('new').'. Aici apar produsele marcate ca noi in catalog.',
            'bestsellers' => 'TOP vanzari: '.route('bestsellers').'. Aici sunt produsele marcate ca populare.',
            'delivery' => 'Livrare si plata: '.route('page', 'delivery-payment').'. Livrarea se face in Moldova, cu plata ramburs sau metoda agreata.',
            'warranty' => 'Garanție: '.route('page', 'warranty').'. Termenul de bază este de 12 luni; condițiile trebuie precizate pentru fiecare produs.',
            'return' => 'Retur: '.route('page', 'returns').'. Clientul poate verifica termenii pentru retur si rambursare.',
            'contact' => 'Contact: '.route('page', 'contacts').'. Pentru alegere complexa, clientul poate cere ajutorul consultantului.',
        ];

        $rules = [
            'catalog' => ['catalog', 'categorie', 'filtru', 'search', 'caut', 'каталог', 'категор', 'фильтр', 'поиск'],
            'product' => ['produs', 'card', 'carte', 'add', 'adaug', 'cumpar', 'товар', 'карточ', 'добав', 'куп'],
            'cart' => ['cos', 'cart', 'корз'],
            'checkout' => ['checkout', 'comanda', 'finaliz', 'заказ', 'оформ'],
            'account' => ['cont', 'login', 'register', 'account', 'аккаунт', 'вход', 'регистрац'],
            'admin' => ['admin', 'administrator', 'админ', 'администратор'],
            'wishlist' => ['favorite', 'wishlist', 'salvez', 'избран'],
            'compare' => ['compar', 'compare', 'сравн'],
            'promotions' => ['promot', 'reducer', 'oferta', 'акци', 'скид'],
            'new' => ['noutati', 'nou', 'noi', 'новин', 'новые'],
            'bestsellers' => ['top', 'vanzari', 'popular', 'топ', 'популяр'],
            'delivery' => ['livrare', 'plata', 'delivery', 'достав', 'оплат'],
            'warranty' => ['garantie', 'warranty', 'гарант'],
            'return' => ['retur', 'ramburs', 'return', 'возврат'],
            'contact' => ['contact', 'telefon', 'email', 'контакт', 'телефон'],
        ];

        $matched = [];
        foreach ($rules as $key => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($prompt, $needle)) {
                    $matched[$key] = $dictionary[$key];
                    break;
                }
            }
        }

        if ($matched === []) {
            $matched = array_intersect_key($dictionary, array_flip(['catalog', 'product', 'cart', 'checkout']));
        }

        return array_values($matched);
    }

    private function budgetFromPrompt(string $prompt): ?int
    {
        preg_match_all('/\b([1-9][0-9]{2,5})\b/u', $prompt, $matches);

        if ($matches[1] === []) {
            return null;
        }

        return max(array_map('intval', $matches[1]));
    }
}
