<?php

namespace App\Http\Requests\Api\CustomerAuth;

use Illuminate\Foundation\Http\FormRequest;

class LogoutCustomerSessionRequest extends FormRequest
{
    public const REFRESH_TOKEN = 'refresh_token';

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
            self::REFRESH_TOKEN => ['required', 'string'],
        ];
    }
}
