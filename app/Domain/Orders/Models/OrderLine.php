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
    public const UNIT_PRICE_AMOUNT = 'unit_price_amount';
    public const QUANTITY = 'quantity';
    public const LINE_TOTAL_AMOUNT = 'line_total_amount';

    protected $fillable = [
        self::ORDER_ID,
        self::PRODUCT_ID,
        self::PRODUCT_NAME,
        self::UNIT_PRICE_AMOUNT,
        self::QUANTITY,
        self::LINE_TOTAL_AMOUNT,
    ];

    protected function casts(): array
    {
        return [
            self::UNIT_PRICE_AMOUNT => 'integer',
            self::QUANTITY => 'integer',
            self::LINE_TOTAL_AMOUNT => 'integer',
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
