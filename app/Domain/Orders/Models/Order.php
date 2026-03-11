<?php

namespace App\Domain\Orders\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\DeliveryMethodEnum;
use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Enums\PaymentMethodEnum;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const ID = 'id';
    public const SITE_ID = 'site_id';
    public const CUSTOMER_ID = 'customer_id';
    public const REFERENCE_SEQUENCE = 'reference_sequence';
    public const REFERENCE = 'reference';
    public const STATUS = 'status';
    public const PAYMENT_METHOD = 'payment_method';
    public const DELIVERY_METHOD = 'delivery_method';
    public const DELIVERY_AMOUNT = 'delivery_amount';
    public const TOTAL_AMOUNT = 'total_amount';
    public const FULL_NAME = 'full_name';
    public const FULL_ADDRESS = 'full_address';
    public const CITY = 'city';
    public const COUNTRY = 'country';

    protected $fillable = [
        self::SITE_ID,
        self::CUSTOMER_ID,
        self::REFERENCE_SEQUENCE,
        self::REFERENCE,
        self::STATUS,
        self::PAYMENT_METHOD,
        self::DELIVERY_METHOD,
        self::DELIVERY_AMOUNT,
        self::TOTAL_AMOUNT,
        self::FULL_NAME,
        self::FULL_ADDRESS,
        self::CITY,
        self::COUNTRY,
    ];

    protected function casts(): array
    {
        return [
            self::STATUS => OrderStatusEnum::class,
            self::PAYMENT_METHOD => PaymentMethodEnum::class,
            self::DELIVERY_METHOD => DeliveryMethodEnum::class,
            self::REFERENCE_SEQUENCE => 'integer',
            self::DELIVERY_AMOUNT => 'integer',
            self::TOTAL_AMOUNT => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }
}
