<?php

namespace App\Domain\Shared\SiteContext;

use Illuminate\Http\Request;

class CurrentSiteResolver
{
    public const REQUEST_ATTRIBUTE = 'current_site';
    private const HEADER_NAME = 'X-Site-Code';

    public function resolve(Request $request): ?Site
    {
        $site = $this->resolveFromHost($request);

        if ($site instanceof Site) {
            return $site;
        }

        if (! $this->isHeaderFallbackEnabled()) {
            return null;
        }

        return $this->resolveFromHeader($request);
    }

    private function resolveFromHost(Request $request): ?Site
    {
        $host = $this->normalizeHost($request->getHost());

        if ($host === null) {
            return null;
        }

        return Site::query()
            ->where(Site::DOMAIN, $host)
            ->first();
    }

    private function resolveFromHeader(Request $request): ?Site
    {
        $siteCode = $request->headers->get(self::HEADER_NAME);

        if (! is_string($siteCode) || $siteCode === '') {
            return null;
        }

        return Site::query()
            ->where(Site::CODE, $siteCode)
            ->first();
    }

    private function isHeaderFallbackEnabled(): bool
    {
        return (bool) config('app.site_header_fallback_enabled', false);
    }

    private function normalizeHost(?string $host): ?string
    {
        if (! is_string($host) || $host === '') {
            return null;
        }

        $normalizedHost = strtolower(preg_replace('/:\d+$/', '', $host) ?? '');

        return $normalizedHost === '' ? null : $normalizedHost;
    }
}
