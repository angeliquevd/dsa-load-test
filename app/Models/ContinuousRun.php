<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContinuousRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
        ];
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function incrementCycle(int $errors = 0): void
    {
        $this->increment('total_cycles');
        $this->increment('total_statements', 300);
        if ($errors > 0) {
            $this->increment('total_errors', $errors);
        }
    }

    public function incrementSingleCycle(int $statementsCount, int $errors = 0): void
    {
        $this->increment('total_single_cycles');
        $this->increment('total_single_statements', $statementsCount);
        if ($errors > 0) {
            $this->increment('total_single_errors', $errors);
        }
    }

    public function stop(): void
    {
        $this->update([
            'status' => 'stopped',
            'stopped_at' => now(),
        ]);
    }

    public function getDurationInSeconds(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->stopped_at ?? now();

        return $endTime->diffInSeconds($this->started_at);
    }

    public function getStatementsPerSecond(): float
    {
        $duration = $this->getDurationInSeconds();

        if (! $duration || $duration === 0) {
            return 0;
        }

        return round($this->total_statements / $duration, 2);
    }

    public function getSingleStatementsPerSecond(): float
    {
        $duration = $this->getDurationInSeconds();

        if (! $duration || $duration === 0) {
            return 0;
        }

        return round($this->total_single_statements / $duration, 2);
    }
}
