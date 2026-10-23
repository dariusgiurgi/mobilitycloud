<?php

namespace App\Support;

use Throwable;

class ArchivePerformance
{
    private int $started;

    private array $phases = [];

    private array $counters = [];

    public function __construct()
    {
        $this->started = hrtime(true);
    }

    public function measure(string $phase, callable $work): mixed
    {
        $started = hrtime(true);
        $this->phases[$phase] ??= ['nanoseconds' => 0, 'calls' => 0, 'failures' => 0];
        $this->phases[$phase]['calls']++;
        try {
            return $work();
        } catch (Throwable $exception) {
            $this->phases[$phase]['failures']++;
            throw $exception;
        } finally {
            $this->phases[$phase]['nanoseconds'] += hrtime(true) - $started;
        }
    }

    public function increment(string $counter, int $amount = 1): void
    {
        $this->counters[$counter] = ($this->counters[$counter] ?? 0) + $amount;
    }

    public function summary(): array
    {
        return [
            'duration_ms' => round((hrtime(true) - $this->started) / 1_000_000, 3),
            'phases' => array_map(fn (array $phase): array => [
                'duration_ms' => round($phase['nanoseconds'] / 1_000_000, 3),
                'calls' => $phase['calls'], 'failures' => $phase['failures'],
            ], $this->phases),
            'counters' => $this->counters,
            // A long-lived worker's peak can include earlier attempts.
            'process_peak_memory_bytes' => memory_get_peak_usage(true),
        ];
    }
}
