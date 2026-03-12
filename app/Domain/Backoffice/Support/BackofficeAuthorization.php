<?php

namespace App\Domain\Backoffice\Support;

use App\Domain\Backoffice\Models\BackofficeAgent;
use Illuminate\Auth\Access\AuthorizationException;

final class BackofficeAuthorization
{
    public function ensureCanViewRecentOrders(BackofficeAgent $agent): void
    {
        if ($this->canViewRecentOrders($agent)) {
            return;
        }

        throw new AuthorizationException();
    }

    public function canViewRecentOrders(BackofficeAgent $agent): bool
    {
        return (int) $agent->getKey() === 1
            || (bool) $agent->getAttribute(BackofficeAgent::CAN_VIEW_RECENT_ORDERS);
    }

    public function ensureCanCreateProducts(BackofficeAgent $agent): void
    {
        if ($this->canCreateProducts($agent)) {
            return;
        }

        throw new AuthorizationException();
    }

    public function canCreateProducts(BackofficeAgent $agent): bool
    {
        return (int) $agent->getKey() === 1
            || (bool) $agent->getAttribute(BackofficeAgent::CAN_CREATE_PRODUCTS);
    }
}
