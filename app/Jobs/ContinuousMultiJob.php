<?php

namespace App\Jobs;

use App\Models\ContinuousRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ContinuousMultiJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public int $continuousRunId) {}

    public function handle(): void
    {
        $continuousRun = ContinuousRun::find($this->continuousRunId);

        if (! $continuousRun || ! $continuousRun->isRunning()) {
            Log::info('[CONTINUOUS] Stopping - run not found or already stopped', [
                'continuous_run_id' => $this->continuousRunId,
            ]);

            return;
        }

        Log::info('[CONTINUOUS] Starting cycle', [
            'continuous_run_id' => $this->continuousRunId,
            'cycle' => $continuousRun->total_cycles + 1,
        ]);

        $errorsInCycle = 0;

        // Dispatch 3 FireStatement jobs synchronously
        for ($i = 0; $i < 3; $i++) {
            try {
                $jobId = uniqid('continuous_');
                FireStatement::dispatchSync($jobId);
            } catch (\Exception $e) {
                $errorsInCycle++;
                Log::error('[CONTINUOUS] Job failed in cycle', [
                    'continuous_run_id' => $this->continuousRunId,
                    'job_index' => $i,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Re-fetch to check if stopped during execution
        $continuousRun->refresh();

        if (! $continuousRun->isRunning()) {
            Log::info('[CONTINUOUS] Stopped during execution', [
                'continuous_run_id' => $this->continuousRunId,
            ]);

            return;
        }

        // Update stats
        $continuousRun->incrementCycle($errorsInCycle);

        Log::info('[CONTINUOUS] Cycle completed', [
            'continuous_run_id' => $this->continuousRunId,
            'total_cycles' => $continuousRun->total_cycles,
            'total_statements' => $continuousRun->total_statements,
        ]);

        // Self-dispatch to continue the loop
        self::dispatch($this->continuousRunId);
    }
}
