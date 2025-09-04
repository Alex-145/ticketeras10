<?php

namespace App\Support\Concerns;

use Illuminate\Support\Str;

trait HasTraceLogging
{
    public string $traceId;

    public function bootTrace(): void
    {
        $this->traceId = $this->traceId ?? (string) Str::uuid();
    }

    protected function ctx(array $extra = []): array
    {
        return array_merge([
            'comp'    => static::class,
            'trace'   => $this->traceId ?? 'n/a',
            'user_id' => auth()->id(),
        ], $extra);
    }
}
