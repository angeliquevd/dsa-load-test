<?php

namespace App\Http\Controllers;

use App\Jobs\ContinuousMultiJob;
use App\Models\ContinuousRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContinuousController extends Controller
{
    public function index(): View
    {
        $currentRun = ContinuousRun::where('status', 'running')->latest()->first();
        $lastRun = ContinuousRun::where('status', 'stopped')->latest()->first();

        return view('continuous', [
            'currentRun' => $currentRun,
            'lastRun' => $lastRun,
            'isRunning' => $currentRun !== null,
        ]);
    }

    public function start(): RedirectResponse
    {
        // Stop any existing running processes first
        ContinuousRun::where('status', 'running')->update([
            'status' => 'stopped',
            'stopped_at' => now(),
        ]);

        // Create a new continuous run
        $continuousRun = ContinuousRun::create([
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Dispatch the continuous job
        ContinuousMultiJob::dispatch($continuousRun->id);

        return back()->with('status', 'Continuous execution started. Sending 300 SORs per cycle.');
    }

    public function stop(): RedirectResponse
    {
        $stoppedCount = ContinuousRun::where('status', 'running')->update([
            'status' => 'stopped',
            'stopped_at' => now(),
        ]);

        if ($stoppedCount > 0) {
            return back()->with('status', 'Continuous execution stopped.');
        }

        return back()->with('status', 'No active continuous execution to stop.');
    }

    public function stats(): JsonResponse
    {
        $currentRun = ContinuousRun::where('status', 'running')->latest()->first();

        if (! $currentRun) {
            $lastRun = ContinuousRun::where('status', 'stopped')->latest()->first();

            return response()->json([
                'isRunning' => false,
                'run' => $lastRun ? [
                    'status' => $lastRun->status,
                    'total_cycles' => $lastRun->total_cycles,
                    'total_statements' => $lastRun->total_statements,
                    'total_errors' => $lastRun->total_errors,
                    'duration_seconds' => $lastRun->getDurationInSeconds(),
                    'statements_per_second' => $lastRun->getStatementsPerSecond(),
                    'started_at' => $lastRun->started_at?->toDateTimeString(),
                    'stopped_at' => $lastRun->stopped_at?->toDateTimeString(),
                ] : null,
            ]);
        }

        return response()->json([
            'isRunning' => true,
            'run' => [
                'status' => $currentRun->status,
                'total_cycles' => $currentRun->total_cycles,
                'total_statements' => $currentRun->total_statements,
                'total_errors' => $currentRun->total_errors,
                'duration_seconds' => $currentRun->getDurationInSeconds(),
                'statements_per_second' => $currentRun->getStatementsPerSecond(),
                'started_at' => $currentRun->started_at?->toDateTimeString(),
                'stopped_at' => $currentRun->stopped_at?->toDateTimeString(),
            ],
        ]);
    }
}
