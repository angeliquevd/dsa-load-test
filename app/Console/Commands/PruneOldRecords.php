<?php

namespace App\Console\Commands;

use App\Models\ApiError;
use App\Models\ContinuousRun;
use Illuminate\Console\Command;

class PruneOldRecords extends Command
{
    protected $signature = 'app:prune-old-records {--days=7 : Number of days to keep records}';

    protected $description = 'Prune old API errors and completed continuous runs';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $errorsDeleted = ApiError::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $runsDeleted = ContinuousRun::query()
            ->where('status', 'stopped')
            ->where('stopped_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$errorsDeleted} API errors and {$runsDeleted} continuous runs older than {$days} days.");

        return self::SUCCESS;
    }
}
