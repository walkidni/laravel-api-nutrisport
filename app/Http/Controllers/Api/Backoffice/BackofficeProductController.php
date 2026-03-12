<?php

namespace App\Http\Controllers\Api\Backoffice;

use App\Domain\Backoffice\Actions\CreateBackofficeProductAction;
use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Backoffice\CreateBackofficeProductRequest;
use App\Http\Resources\Api\Backoffice\BackofficeProductResource;
use Illuminate\Http\JsonResponse;

class BackofficeProductController extends Controller
{
    public function store(
        CreateBackofficeProductRequest $request,
        CreateBackofficeProductAction $createBackofficeProductAction,
    ): JsonResponse {
        /** @var BackofficeAgent $agent */
        $agent = $request->user('backoffice');

        return BackofficeProductResource::make(
            $createBackofficeProductAction($agent, $request->validated()),
        )->response()->setStatusCode(201);
    }
}
