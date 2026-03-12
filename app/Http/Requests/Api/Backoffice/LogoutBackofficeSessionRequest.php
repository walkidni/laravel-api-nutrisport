<?php

namespace App\Http\Requests\Api\Backoffice;

use Illuminate\Foundation\Http\FormRequest;

class LogoutBackofficeSessionRequest extends FormRequest
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
