<?php

namespace App\Domain\Cart\Services;

use Illuminate\Support\Facades\Cache;

class CartStorage
{
    /**
     * @return array<string, mixed>|null
     */
    public function find(string $siteCode, string $token): ?array
    {
        $payload = Cache::get($this->makeKey($siteCode, $token));

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function put(string $siteCode, string $token, array $payload): void
    {
        Cache::put(
            $this->makeKey($siteCode, $token),
            $payload,
            (int) config('cart.ttl_seconds'),
        );
    }

    public function forget(string $siteCode, string $token): void
    {
        Cache::forget($this->makeKey($siteCode, $token));
    }

    public function makeKey(string $siteCode, string $token): string
    {
        return "cart:{$siteCode}:{$token}";
    }
}
