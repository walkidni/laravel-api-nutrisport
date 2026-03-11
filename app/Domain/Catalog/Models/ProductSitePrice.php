<?php

namespace App\Domain\Catalog\Models;

use Database\Factories\ProductSitePriceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSitePrice extends Model
{
    /** @use HasFactory<ProductSitePriceFactory> */
    use HasFactory;

    public const PRODUCT_ID = 'product_id';
    public const SITE_ID = 'site_id';
    public const PRICE_AMOUNT_CENTS = 'price_amount_cents';

    protected $fillable = [
        self::PRODUCT_ID,
        self::SITE_ID,
        self::PRICE_AMOUNT_CENTS,
    ];

    protected static function newFactory(): ProductSitePriceFactory
    {
        return ProductSitePriceFactory::new();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Shared\SiteContext\Site::class);
    }
}
