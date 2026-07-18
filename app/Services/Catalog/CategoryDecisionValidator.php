<?php

namespace App\Services\Catalog;

use App\Models\Product;

class CategoryDecisionValidator
{
    public function __construct(
        private CategoryTaxonomy $taxonomy,
        private CategoryCandidateService $candidates,
    ) {}

    public function validate(Product $product, ?string $slug): array
    {
        $errors = [];
        $category = $slug ? $this->taxonomy->findAssignable($slug) : null;

        if (! $category) {
            return ['unknown_or_non_assignable_category'];
        }

        $product->loadMissing('category');
        if ($product->category
            && $product->category->id !== $category->id
            && $this->taxonomy->isInBranch($product->category, $category->slug)) {
            $errors[] = 'cannot_replace_specific_category_with_ancestor';
        }

        $input = $this->candidates->input($product);
        $text = $this->candidates->normalize(implode(' ', array_filter([
            $input['name_ru'], $input['name_ro'], $input['source_title'],
            $input['source_subgroup'], $input['source_category_path'], implode(' ', $input['attributes']),
        ])));
        $titleText = $this->candidates->normalize(implode(' ', array_filter([
            $input['name_ru'], $input['name_ro'], $input['source_title'],
        ])));

        $hasAny = fn (array $phrases): bool => collect($phrases)->contains(
            fn (string $phrase) => $this->candidates->containsPhrase($text, $this->candidates->normalize($phrase))
        );
        $hasFragment = fn (array $phrases): bool => $this->candidates->containsAnyFragment($text, $phrases);
        $titleStartsWith = fn (array $phrases): bool => collect($phrases)->contains(
            fn (string $phrase) => str_starts_with($titleText, $this->candidates->normalize($phrase))
        );

        if ($titleStartsWith(['инструментальная тележка', 'тележка для инструментов', 'тележка с инструментом', 'tool trolley', 'tool cart'])
            && ! $this->taxonomy->isInBranch($category, 'mobilier-pentru-service')) {
            $errors[] = 'tool_trolley_outside_workshop_furniture';
        }

        if ($hasAny(['рабочие перчатки', 'защитные очки', 'защитная маска', 'work gloves', 'safety goggles'])
            && ! $this->taxonomy->isInBranch($category, 'echipament-protectie')) {
            $errors[] = 'protective_equipment_outside_ppe';
        }

        if ($hasFragment(['диэлектр', 'изолирован', '1000v', '1000 v', 'vde', 'зачистк', 'кабелерез', 'стриппер', 'обжимн', 'оптоволок', 'crimping pliers', 'wire stripper', 'fiber optic'])
            && ! $this->taxonomy->isInBranch($category, 'instrumente-electromontaj')) {
            $errors[] = 'electrical_hand_tool_outside_electromontaj';
        }

        if ($category->slug === 'seturi-de-scule'
            && $hasFragment(['демонтаж', 'форсунк', 'грм', 'кпп', 'сцеплен', 'тнвд', 'двигател', 'ремн', 'injector', 'timing', 'clutch'])) {
            $errors[] = 'specialized_automotive_kit_in_generic_tool_sets';
        }

        if ($category->slug === 'echipamente-schimb-ulei'
            && $hasFragment(['рулевой рейк', 'пыльник рулев', 'steering rack boot'])) {
            $errors[] = 'steering_tool_in_oil_equipment';
        }

        if ($category->slug === 'testere-electrice-si-indicatoare'
            && $hasFragment(['набор инструмент', 'комплект инструмент', 'tool set', 'trusa'])) {
            $errors[] = 'mixed_electrical_tool_set_in_testers';
        }

        if ($hasAny(['аккумулятор li ion', 'аккумулятор lihd', 'battery pack', 'зарядное устройство для аккумулятора'])
            && $category->slug !== 'baterii-incarcatoare') {
            $errors[] = 'battery_outside_batteries';
        }

        if ($hasAny(['гидравлический пресс', 'hydraulic press']) && $category->slug !== 'prese-hidraulice') {
            $errors[] = 'hydraulic_press_outside_presses';
        }

        if ($hasAny(['воздушный компрессор', 'air compressor']) && $category->slug !== 'compresoare') {
            $errors[] = 'compressor_outside_compressors';
        }

        $pneumatic = $hasAny(['пневматический', 'пневматическая', 'пневмо', 'air tool']);
        if ($pneumatic && ($this->taxonomy->isInBranch($category, 'instrumente-de-masurare')
            || $this->taxonomy->isInBranch($category, 'instrumente-electromontaj'))) {
            $errors[] = 'pneumatic_tool_in_incompatible_branch';
        }

        return array_values(array_unique($errors));
    }
}
