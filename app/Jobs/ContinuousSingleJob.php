<?php

namespace App\Jobs;

use App\Models\ContinuousRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ContinuousSingleJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    private const BATCH_SIZE = 1000;

    public function __construct(public int $continuousRunId) {}

    public function handle(): void
    {
        $continuousRun = ContinuousRun::find($this->continuousRunId);

        if (! $continuousRun || ! $continuousRun->isRunning()) {
            Log::info('[CONTINUOUS-SINGLE] Stopping - run not found or already stopped', [
                'continuous_run_id' => $this->continuousRunId,
            ]);

            return;
        }

        Log::info('[CONTINUOUS-SINGLE] Starting cycle', [
            'continuous_run_id' => $this->continuousRunId,
            'cycle' => $continuousRun->total_single_cycles + 1,
        ]);

        $dispatchErrors = 0;

        // Dispatch 1000 FireSingleStatement jobs (stats are tracked on job completion)
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            try {
                $jobId = uniqid('continuous_single_');
                FireSingleStatement::dispatch($jobId, $this->continuousRunId);
            } catch (\Exception $e) {
                $dispatchErrors++;
                Log::error('[CONTINUOUS-SINGLE] Job dispatch failed in cycle', [
                    'continuous_run_id' => $this->continuousRunId,
                    'job_index' => $i,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Re-fetch to check if stopped during execution
        $continuousRun->refresh();

        if (! $continuousRun->isRunning()) {
            Log::info('[CONTINUOUS-SINGLE] Stopped during execution', [
                'continuous_run_id' => $this->continuousRunId,
            ]);

            return;
        }

        // Only increment cycle count - statement counts are tracked on job completion
        $continuousRun->increment('total_single_cycles');
        if ($dispatchErrors > 0) {
            $continuousRun->increment('total_single_errors', $dispatchErrors);
        }

        Log::info('[CONTINUOUS-SINGLE] Cycle dispatched', [
            'continuous_run_id' => $this->continuousRunId,
            'total_single_cycles' => $continuousRun->total_single_cycles,
            'jobs_dispatched' => self::BATCH_SIZE - $dispatchErrors,
        ]);

        // Self-dispatch to continue the loop
        self::dispatch($this->continuousRunId);
    }
}
