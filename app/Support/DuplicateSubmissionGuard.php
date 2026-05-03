<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DuplicateSubmissionGuard
{
    public function acquire(string $scope, array $payload, int $seconds = 30, ?Authenticatable $user = null): bool
    {
        $subjectId = $user?->getAuthIdentifier() ?? auth()->id() ?? 'guest';

        return Cache::add(
            $this->makeKey($scope, $subjectId, $payload),
            now()->toIso8601String(),
            $seconds
        );
    }

    public function acquireFromRequest(Request $request, string $scope, ?array $payload = null, int $seconds = 30): bool
    {
        return $this->acquire(
            $scope,
            $payload ?? $request->all(),
            $seconds,
            $request->user()
        );
    }

    protected function makeKey(string $scope, mixed $subjectId, array $payload): string
    {
        return 'duplicate-submission:' . sha1(json_encode([
            'scope' => $scope,
            'subject' => $subjectId,
            'payload' => $this->normalize($payload),
        ], JSON_THROW_ON_ERROR));
    }

    protected function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item) => $this->normalize($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalize($item);
        }

        return $value;
    }
}
