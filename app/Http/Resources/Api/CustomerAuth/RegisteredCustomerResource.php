<?php

namespace App\Http\Resources\Api\CustomerAuth;

use App\Domain\Customers\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisteredCustomerResource extends JsonResource
{
    public function __construct(Customer $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @param Customer $resource
     * @return array{id: int, email: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource->getKey(),
            'email' => (string) $this->resource->getAttribute(Customer::EMAIL),
        ];
    }
}
