<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\DTOs;

final readonly class CheckResult
{
    public function __construct(
        public string $check,
        public string $status,
        public string $output,
        public float $durationSeconds,
    ) {}

    public function isPassed(): bool
    {
        return $this->status === 'passed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    /**
     * @return array{check: string, status: string, output: string, duration_seconds: float}
     */
    public function toArray(): array
    {
        return [
            'check' => $this->check,
            'status' => $this->status,
            'output' => $this->output,
            'duration_seconds' => $this->durationSeconds,
        ];
    }

    /**
     * @param  array{check: string, status: string, output?: string, duration_seconds?: float}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            check: $data['check'],
            status: $data['status'],
            output: $data['output'] ?? '',
            durationSeconds: (float) ($data['duration_seconds'] ?? 0.0),
        );
    }
}
