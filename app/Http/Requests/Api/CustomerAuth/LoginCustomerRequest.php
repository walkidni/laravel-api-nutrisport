<?php

namespace App\Http\Requests\Api\CustomerAuth;

use App\Domain\Customers\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class LoginCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            Customer::EMAIL => ['required', 'email'],
            Customer::PASSWORD => ['required', 'string'],
        ];
    }
}
