<?php

namespace App\Http\Controllers;

use App\Models\ApiError;
use App\Models\ContinuousRun;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function showMetrics()
    {
        $latestRun = ContinuousRun::query()->latest()->first();

        $totalStatements = ContinuousRun::query()->sum('total_statements');
        $totalSingleStatements = ContinuousRun::query()->sum('total_single_statements');
        $totalErrors = ContinuousRun::query()->sum('total_errors');
        $totalSingleErrors = ContinuousRun::query()->sum('total_single_errors');

        $duration = null;
        $durationInSeconds = null;
        $statementsPerSecond = 0;
        $singleStatementsPerSecond = 0;

        if ($latestRun) {
            $durationInSeconds = $latestRun->getDurationInSeconds();
            $duration = $durationInSeconds ? gmdate('H:i:s', $durationInSeconds) : null;
            $statementsPerSecond = $latestRun->getStatementsPerSecond();
            $singleStatementsPerSecond = $latestRun->getSingleStatementsPerSecond();
        }

        $apiErrorCount = ApiError::query()->count();
        $apiErrorsByStatus = ApiError::query()
            ->select('status_code', DB::raw('count(*) as total'))
            ->groupBy('status_code')
            ->orderBy('total', 'desc')
            ->get();

        return view('metrics', [
            'latestRun' => $latestRun,
            'totalStatements' => $totalStatements,
            'totalSingleStatements' => $totalSingleStatements,
            'totalErrors' => $totalErrors,
            'totalSingleErrors' => $totalSingleErrors,
            'duration' => $duration,
            'durationInSeconds' => $durationInSeconds,
            'statementsPerSecond' => $statementsPerSecond,
            'singleStatementsPerSecond' => $singleStatementsPerSecond,
            'apiErrorCount' => $apiErrorCount,
            'apiErrorsByStatus' => $apiErrorsByStatus,
        ]);
    }

    public function truncateResponses()
    {
        ContinuousRun::query()->truncate();
        ApiError::query()->truncate();

        return redirect()->route('metrics')->with('success', 'All run statistics and API error logs have been deleted.');
    }
}
