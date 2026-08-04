<?php

namespace App\Console\Commands;

use App\Services\ParserQueueSupervisor;
use Illuminate\Console\Command;

class DrainParserQueue extends Command
{
    protected $signature = 'parser:drain
        {--lane=all : Queue lane: fast, slow, images, or all}
        {--watch : Keep checking for new parser jobs}';

    protected $description = 'Safely drain parser queues with a single cross-process worker lock';

    public function handle(ParserQueueSupervisor $supervisor): int
    {
        do {
            $supervisor->drain((string) $this->option('lane'));

            if (! $this->option('watch')) {
                break;
            }

            sleep(2);
        } while (true);

        return self::SUCCESS;
    }
}
