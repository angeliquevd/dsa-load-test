<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DispatchSingleStatements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $total;
    private $offset;
    private $batchSize;

    /**
     * Create a new job instance.
     *
     * @param int $total Total number of statements to dispatch
     * @param int $offset The starting point for this batch
     * @param int $batchSize Number of jobs to dispatch in this batch
     * @return void
     */
    public function __construct(int $total, int $offset = 0, int $batchSize = 1000)
    {
        $this->total = $total;
        $this->offset = $offset;
        $this->batchSize = $batchSize;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // On the first run, reset all relevant cache keys
        if ($this->offset === 0) {
            Cache::forget('single_processing_start');
            Cache::forget('single_processing_end');
            Cache::forget('single_processed_count');
            Cache::forget('total_single_count');

            // Set initial values
            Cache::put('single_processing_start', null, now()->addHours(1));
            Cache::put('single_processing_end', null, now()->addHours(1));
            Cache::put('single_processed_count', 0, now()->addHours(1));
            Cache::put('total_single_count', $this->total, now()->addHours(1));

//            Log::info("[METRICS] Starting to dispatch {$this->total} single statements in batches of {$this->batchSize}");
        }

        // Determine the upper limit for this batch
        $limit = $this->offset + $this->batchSize;
        if ($limit > $this->total) {
            $limit = $this->total;
        }

        // Dispatch jobs for the current batch
        for ($i = $this->offset + 1; $i <= $limit; $i++) {
            FireSingleStatement::dispatch($i);
        }

        // If there are more statements to dispatch, create a job for the next batch
        if ($limit < $this->total) {
            self::dispatch($this->total, $limit, $this->batchSize);
        } else {
            Log::info("[METRICS] Completed dispatching all {$this->total} single statements");
        }
    }
}
