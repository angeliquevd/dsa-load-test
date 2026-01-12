<?php

use App\Http\Controllers\ContinuousController;
use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/fire', [\App\Http\Controllers\LaunchController::class, 'fire'])->name('fire');
Route::get('/single', function () {
    return view('single');
})->name('single');
Route::post('/fire-single', [\App\Http\Controllers\LaunchController::class, 'fireSingle'])->name('fire-single');

Route::get('/continuous', [ContinuousController::class, 'index'])->name('continuous');
Route::post('/continuous/start', [ContinuousController::class, 'start'])->name('continuous.start');
Route::post('/continuous/stop', [ContinuousController::class, 'stop'])->name('continuous.stop');
Route::get('/continuous/stats', [ContinuousController::class, 'stats'])->name('continuous.stats');

Route::get('/metrics', [MetricsController::class, 'showMetrics'])->name('metrics');
Route::post('/metrics/truncate', [MetricsController::class, 'truncateResponses'])->name('metrics.truncate');
