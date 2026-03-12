<?php

namespace App\Http\Requests\Api\Backoffice;

use App\Domain\Backoffice\Models\BackofficeAgent;
use Illuminate\Foundation\Http\FormRequest;

class LoginBackofficeAgentRequest extends FormRequest
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
            BackofficeAgent::EMAIL => ['required', 'email'],
            BackofficeAgent::PASSWORD => ['required', 'string'],
        ];
    }
}
