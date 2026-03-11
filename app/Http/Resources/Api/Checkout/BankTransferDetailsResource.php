<?php

namespace App\Http\Resources\Api\Checkout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankTransferDetailsResource extends JsonResource
{
    /**
     * @param array{account_holder: string, iban: string, bic: string, bank_name: string} $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{account_holder: string, iban: string, bic: string, bank_name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'account_holder' => $this->resource['account_holder'],
            'iban' => $this->resource['iban'],
            'bic' => $this->resource['bic'],
            'bank_name' => $this->resource['bank_name'],
        ];
    }
}
