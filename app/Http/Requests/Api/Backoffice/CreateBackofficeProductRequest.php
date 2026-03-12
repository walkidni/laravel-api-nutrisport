<?php

namespace App\Http\Requests\Api\Backoffice;

use App\Domain\Catalog\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBackofficeProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Rule|string>>
     */
    public function rules(): array
    {
        return [
            Product::NAME => ['required', 'string'],
            'initial_stock' => ['required', 'integer', 'min:0'],
            'site_prices' => ['required', 'array', 'min:1'],
            'site_prices.*.site_code' => ['required', 'string', 'distinct', Rule::exists('sites', 'code')],
            'site_prices.*.price' => ['required', 'string', 'regex:/^\d+(?:\.\d{1,2})?$/'],
        ];
    }
}
