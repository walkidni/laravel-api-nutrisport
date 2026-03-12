<?php

namespace App\Http\Controllers\Api\Backoffice;

use App\Domain\Backoffice\Actions\ListRecentOrdersAction;
use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Backoffice\ListRecentOrdersRequest;
use App\Http\Resources\Api\Backoffice\BackofficeRecentOrderResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BackofficeOrderController extends Controller
{
    public function index(
        ListRecentOrdersRequest $request,
        ListRecentOrdersAction $listRecentOrdersAction,
    ): AnonymousResourceCollection {
        /** @var BackofficeAgent $agent */
        $agent = $request->user('backoffice');

        return BackofficeRecentOrderResource::collection(
            $listRecentOrdersAction($agent, $request->validated()),
        );
    }
}
