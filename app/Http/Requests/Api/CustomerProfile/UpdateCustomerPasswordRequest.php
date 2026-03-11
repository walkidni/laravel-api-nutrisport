<?php

namespace App\Http\Requests\Api\CustomerProfile;

use App\Domain\Customers\Models\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerPasswordRequest extends FormRequest
{
    public const CURRENT_PASSWORD = 'current_password';
    public const PASSWORD_CONFIRMATION = 'password_confirmation';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            self::CURRENT_PASSWORD => ['required', 'string'],
            Customer::PASSWORD => ['required', 'string', 'confirmed'],
        ];
    }
}
