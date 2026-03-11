<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Shared\SiteContext\Site;

class OrderReferenceService
{
    private const SEQUENCE_PADDING = 6;

    /**
     * Must be called inside the same DB transaction that persists the order.
     */
    public function nextSequenceForSite(Site $site): int
    {
        Site::query()
            ->whereKey($site->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $lastSequence = Order::query()
            ->where(Order::SITE_ID, $site->getKey())
            ->orderByDesc(Order::REFERENCE_SEQUENCE)
            ->value(Order::REFERENCE_SEQUENCE);

        return (int) $lastSequence + 1;
    }

    public function formatForSite(Site $site, int $sequence): string
    {
        $prefix = strtoupper((string) $site->getAttribute(Site::CODE));

        return sprintf('%s-%0'.self::SEQUENCE_PADDING.'d', $prefix, $sequence);
    }
}
