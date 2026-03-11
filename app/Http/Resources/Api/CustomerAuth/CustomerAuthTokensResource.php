<?php

namespace App\Http\Resources\Api\CustomerAuth;

use App\Domain\Customers\DTOs\CustomerAuthTokensDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAuthTokensResource extends JsonResource
{
    public function __construct(CustomerAuthTokensDTO $resource)
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
