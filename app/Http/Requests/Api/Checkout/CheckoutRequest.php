<?php

namespace App\Http\Requests\Api\Checkout;

use App\Domain\Orders\Enums\DeliveryMethodEnum;
use App\Domain\Orders\Enums\PaymentMethodEnum;
use App\Domain\Orders\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public const FULL_NAME = Order::FULL_NAME;
    public const FULL_ADDRESS = Order::FULL_ADDRESS;
    public const CITY = Order::CITY;
    public const COUNTRY = Order::COUNTRY;
    public const PAYMENT_METHOD = Order::PAYMENT_METHOD;
    public const DELIVERY_METHOD = Order::DELIVERY_METHOD;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            self::FULL_NAME => ['required', 'string'],
            self::FULL_ADDRESS => ['required', 'string'],
            self::CITY => ['required', 'string'],
            self::COUNTRY => ['required', 'string'],
            self::PAYMENT_METHOD => ['sometimes', 'string', Rule::in([PaymentMethodEnum::BANK_TRANSFER->value])],
            self::DELIVERY_METHOD => ['sometimes', 'string', Rule::in([DeliveryMethodEnum::HOME_DELIVERY->value])],
        ];
    }
}
