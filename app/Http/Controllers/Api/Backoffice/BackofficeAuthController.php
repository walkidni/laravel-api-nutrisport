<?php

namespace App\Http\Controllers\Api\Backoffice;

use App\Domain\Backoffice\Actions\LoginBackofficeAgentAction;
use App\Domain\Backoffice\Actions\LogoutBackofficeSessionAction;
use App\Domain\Backoffice\Actions\RefreshBackofficeSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Backoffice\LoginBackofficeAgentRequest;
use App\Http\Requests\Api\Backoffice\LogoutBackofficeSessionRequest;
use App\Http\Requests\Api\Backoffice\RefreshBackofficeTokenRequest;
use App\Http\Resources\Api\Backoffice\BackofficeAuthTokensResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BackofficeAuthController extends Controller
{
    public function login(
        LoginBackofficeAgentRequest $request,
        LoginBackofficeAgentAction $loginBackofficeAgentAction,
    ): JsonResponse {
        $tokens = $loginBackofficeAgentAction($request->validated());

        return BackofficeAuthTokensResource::make($tokens)->response();
    }

    public function refresh(
        RefreshBackofficeTokenRequest $request,
        RefreshBackofficeSessionAction $refreshBackofficeSessionAction,
    ): JsonResponse {
        $tokens = $refreshBackofficeSessionAction($request->validated());

        return BackofficeAuthTokensResource::make($tokens)->response();
    }

    public function logout(
        LogoutBackofficeSessionRequest $request,
        LogoutBackofficeSessionAction $logoutBackofficeSessionAction,
    ): Response {
        $logoutBackofficeSessionAction($request->validated());

        return response()->noContent();
    }
}
