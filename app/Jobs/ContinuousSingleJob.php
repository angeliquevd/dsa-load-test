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

        $errorsInCycle = 0;
        $successfulDispatches = 0;

        // Dispatch 1000 FireSingleStatement jobs
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            try {
                $jobId = uniqid('continuous_single_');
                FireSingleStatement::dispatch($jobId);
                $successfulDispatches++;
            } catch (\Exception $e) {
                $errorsInCycle++;
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

        // Update stats
        $continuousRun->incrementSingleCycle($successfulDispatches, $errorsInCycle);

        Log::info('[CONTINUOUS-SINGLE] Cycle completed', [
            'continuous_run_id' => $this->continuousRunId,
            'total_single_cycles' => $continuousRun->total_single_cycles,
            'total_single_statements' => $continuousRun->total_single_statements,
        ]);

        // Self-dispatch to continue the loop
        self::dispatch($this->continuousRunId);
    }
}
