<?php

namespace App\Domain\Backoffice\Actions;

use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Domain\Backoffice\Queries\ListRecentOrdersQuery;
use App\Domain\Backoffice\Support\BackofficeAuthorization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListRecentOrdersAction
{
    public function __construct(
        private readonly BackofficeAuthorization $backofficeAuthorization,
        private readonly ListRecentOrdersQuery $listRecentOrdersQuery,
    ) {
    }

    /**
     * @param array{page?: int, per_page?: int} $validated
     */
    public function __invoke(BackofficeAgent $agent, array $validated): LengthAwarePaginator
    {
        $this->backofficeAuthorization->ensureCanViewRecentOrders($agent);

        return ($this->listRecentOrdersQuery)(
            $validated['per_page'] ?? 20,
            $validated['page'] ?? 1,
        );
    }
}
