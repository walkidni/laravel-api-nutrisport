<?php

namespace App\Domain\Customers\Models;

use App\Domain\Shared\SiteContext\Site;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    public const ID = 'id';
    public const SITE_ID = 'site_id';
    public const EMAIL = 'email';
    public const PASSWORD = 'password';

    protected $fillable = [
        self::SITE_ID,
        self::EMAIL,
        self::PASSWORD,
    ];

    protected $hidden = [
        self::PASSWORD,
    ];

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(CustomerRefreshToken::class);
    }

    public function getJWTIdentifier(): int
    {
        return (int) $this->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
