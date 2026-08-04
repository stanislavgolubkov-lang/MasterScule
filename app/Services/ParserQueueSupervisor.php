<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ParserQueueSupervisor
{
    private const QUEUE_LANES = [
        'fast' => ['default', 'parser', 'parser-tristool', 'parser-fast'],
        'images' => ['parser-images', 'parser-image-recovery'],
        'slow' => ['parser-slow'],
    ];

    private bool $scheduled = false;

    /**
     * Start a detached parser worker after Laravel has sent the HTTP response.
     * Every progress-page refresh repeats this cheap operation; the worker lock
     * lets exactly one process drain the queue and recovers immediately after a
     * crashed worker without blocking the web server.
     */
    public function drainAfterResponse(): void
    {
        if ($this->scheduled || app()->runningUnitTests()) {
            return;
        }

        $this->scheduled = true;
        app()->terminating(fn () => $this->startDetachedWorker());
    }

    public function startDetachedWorker(): bool
    {
        if (! $this->hasQueuedWork() || ! function_exists('popen')) {
            return false;
        }

        $php = escapeshellarg(PHP_BINARY);
        $artisan = escapeshellarg(base_path('artisan'));
        $started = false;

        // SQLite permits only one writer. Starting one worker per lane caused
        // otherwise valid parser jobs to collide with "database is locked".
        // Keep lane parallelism for server databases, but serialize the local
        // SQLite installation while preserving fast/images/slow queue priority.
        $lanes = DB::connection()->getDriverName() === 'sqlite'
            ? ['all']
            : array_keys(self::QUEUE_LANES);

        foreach ($lanes as $lane) {
            $command = PHP_OS_FAMILY === 'Windows'
                ? "start \"\" /B {$php} {$artisan} parser:drain --lane={$lane} > NUL 2>&1"
                : "nohup {$php} {$artisan} parser:drain --lane={$lane} > /dev/null 2>&1 &";

            $handle = @popen($command, 'r');
            if (! is_resource($handle)) {
                continue;
            }

            pclose($handle);
            $started = true;
        }

        return $started;
    }

    public function drain(string $lane = 'all'): int
    {
        $queues = $this->queuesForLane($lane);
        if (! $this->hasQueuedWork($lane)) {
            return 0;
        }

        $lockDirectory = storage_path('framework/cache');
        if (! is_dir($lockDirectory)) {
            mkdir($lockDirectory, 0775, true);
        }

        $lockSuffix = app()->runningUnitTests() ? '-testing' : '';
        $handle = fopen($lockDirectory.'/parser-queue-worker-'.$lane.$lockSuffix.'.lock', 'c+');
        if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            return 0;
        }

        try {
            set_time_limit(0);
            $this->releaseOrphanedReservations($queues);

            return Artisan::call('queue:work', [
                'connection' => 'database',
                '--queue' => implode(',', $queues),
                '--sleep' => 1,
                '--tries' => 3,
                '--timeout' => 0,
                '--stop-when-empty' => true,
            ]);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function hasQueuedWork(string $lane = 'all'): bool
    {
        return DB::table(config('queue.connections.database.table', 'jobs'))
            ->whereIn('queue', $this->queuesForLane($lane))
            ->exists();
    }

    private function releaseOrphanedReservations(array $queues): void
    {
        DB::table(config('queue.connections.database.table', 'jobs'))
            ->whereIn('queue', $queues)
            ->whereNotNull('reserved_at')
            ->update(['reserved_at' => null]);
    }

    private function queuesForLane(string $lane): array
    {
        if ($lane === 'all') {
            // Finish and publish each fast TrisTool hit before taking the next
            // bulk row. Slow external recovery remains last and cannot block
            // quick products from appearing in the catalog.
            return [
                'default',
                'parser',
                'parser-tristool',
                'parser-images',
                'parser-image-recovery',
                'parser-fast',
                'parser-slow',
            ];
        }

        return self::QUEUE_LANES[$lane] ?? self::QUEUE_LANES['fast'];
    }
}
