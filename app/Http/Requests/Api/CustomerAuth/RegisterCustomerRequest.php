<?php

namespace App\Http\Requests\Api\CustomerAuth;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>>
     */
    public function rules(): array
    {
        $siteId = app(CurrentSiteContextService::class)->get($this)->getKey();

        return [
            Customer::EMAIL => [
                'required',
                'email',
                Rule::unique('customers', Customer::EMAIL)->where(Customer::SITE_ID, $siteId),
            ],
            Customer::PASSWORD => ['required', 'string'],
        ];
    }
}
