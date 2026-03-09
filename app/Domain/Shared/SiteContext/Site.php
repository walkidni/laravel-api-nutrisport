<?php

namespace App\Domain\Shared\SiteContext;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    public const CODE = 'code';
    public const DOMAIN = 'domain';

    protected $fillable = [
        self::CODE,
        self::DOMAIN,
    ];

    protected static function newFactory(): SiteFactory
    {
        return SiteFactory::new();
    }

    public function productSitePrices(): HasMany
    {
        return $this->hasMany(\App\Domain\Catalog\Models\ProductSitePrice::class);
    }
}
