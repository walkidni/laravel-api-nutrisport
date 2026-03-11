<?php

namespace App\Http\Requests\Api\CustomerProfile;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $site = app(CurrentSiteContextService::class)->get($this);

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        return [
            Customer::FIRST_NAME => ['sometimes', 'string', 'max:255'],
            Customer::LAST_NAME => ['sometimes', 'string', 'max:255'],
            Customer::EMAIL => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('customers', Customer::EMAIL)
                    ->where(Customer::SITE_ID, $site->getKey())
                    ->ignore($customer->getKey()),
            ],
        ];
    }
}
