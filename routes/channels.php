<?php

use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Domain\Backoffice\Support\BackofficeAuthorization;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'backoffice.orders',
    static fn (BackofficeAgent $agent): bool => app(BackofficeAuthorization::class)->canViewRecentOrders($agent),
    ['guards' => ['backoffice']],
);
