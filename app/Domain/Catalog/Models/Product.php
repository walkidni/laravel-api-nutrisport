<?php

namespace App\Domain\Catalog\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    public const NAME = 'name';
    public const STOCK = 'stock';

    protected $fillable = [
        self::NAME,
        self::STOCK,
    ];

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function sitePrices(): HasMany
    {
        return $this->hasMany(ProductSitePrice::class);
    }
}
