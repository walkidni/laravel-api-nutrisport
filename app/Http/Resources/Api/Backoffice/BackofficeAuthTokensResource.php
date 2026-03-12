<?php

namespace App\Http\Resources\Api\Backoffice;

use App\Domain\Backoffice\DTOs\BackofficeAuthTokensDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackofficeAuthTokensResource extends JsonResource
{
    public function __construct(BackofficeAuthTokensDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->resource->accessToken,
            'refresh_token' => $this->resource->refreshToken,
        ];
    }
}
