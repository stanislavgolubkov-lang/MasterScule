<?php

namespace App\Console\Commands;

use App\Services\Catalog\CategoryTaxonomy;
use Illuminate\Console\Command;

class SyncCatalogTaxonomy extends Command
{
    protected $signature = 'masterscule:sync-catalog-taxonomy {--apply : Apply canonical parent and visibility settings}';

    protected $description = 'Preview or synchronize the canonical catalog category tree';

    public function handle(CategoryTaxonomy $taxonomy): int
    {
        $apply = (bool) $this->option('apply');
        $changes = $taxonomy->syncStructure($apply);

        $this->table(
            ['Category', 'Field', 'From', 'To'],
            collect($changes)->take(100)->map(fn (array $change) => [
                $change['slug'],
                $change['field'],
                is_bool($change['from']) ? ($change['from'] ? 'true' : 'false') : ($change['from'] ?? '-'),
                is_bool($change['to']) ? ($change['to'] ? 'true' : 'false') : ($change['to'] ?? '-'),
            ])->all(),
        );

        $this->info(($apply ? 'Applied' : 'Proposed').' taxonomy changes: '.count($changes));

        return self::SUCCESS;
    }
}
