<?php

namespace App\Http\Resources\Api\CustomerProfile;

use App\Domain\Customers\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProfileResource extends JsonResource
{
    public function __construct(Customer $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{id:int, first_name:?string, last_name:?string, email:string}
     */
    public function toArray(Request $request): array
    {
        /** @var Customer $customer */
        $customer = $this->resource;

        return [
            Customer::ID => (int) $customer->getKey(),
            Customer::FIRST_NAME => $customer->getAttribute(Customer::FIRST_NAME),
            Customer::LAST_NAME => $customer->getAttribute(Customer::LAST_NAME),
            Customer::EMAIL => (string) $customer->getAttribute(Customer::EMAIL),
        ];
    }
}
