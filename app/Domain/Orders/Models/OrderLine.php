<?php

namespace App\Domain\Orders\Models;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    public const ID = 'id';
    public const ORDER_ID = 'order_id';
    public const PRODUCT_ID = 'product_id';
    public const PRODUCT_NAME = 'product_name';
    public const UNIT_PRICE_AMOUNT_CENTS = 'unit_price_amount_cents';
    public const QUANTITY = 'quantity';
    public const LINE_TOTAL_AMOUNT_CENTS = 'line_total_amount_cents';

    protected $fillable = [
        self::ORDER_ID,
        self::PRODUCT_ID,
        self::PRODUCT_NAME,
        self::UNIT_PRICE_AMOUNT_CENTS,
        self::QUANTITY,
        self::LINE_TOTAL_AMOUNT_CENTS,
    ];

    protected function casts(): array
    {
        return [
            self::UNIT_PRICE_AMOUNT_CENTS => 'integer',
            self::QUANTITY => 'integer',
            self::LINE_TOTAL_AMOUNT_CENTS => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
