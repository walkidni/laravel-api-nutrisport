<?php

namespace App\Domain\Backoffice\Models;

use Database\Factories\BackofficeAgentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class BackofficeAgent extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<BackofficeAgentFactory> */
    use HasFactory;

    public const ID = 'id';
    public const EMAIL = 'email';
    public const PASSWORD = 'password';
    public const CAN_VIEW_RECENT_ORDERS = 'can_view_recent_orders';
    public const CAN_CREATE_PRODUCTS = 'can_create_products';

    protected $fillable = [
        self::EMAIL,
        self::PASSWORD,
        self::CAN_VIEW_RECENT_ORDERS,
        self::CAN_CREATE_PRODUCTS,
    ];

    protected $hidden = [
        self::PASSWORD,
    ];

    protected function casts(): array
    {
        return [
            self::CAN_VIEW_RECENT_ORDERS => 'boolean',
            self::CAN_CREATE_PRODUCTS => 'boolean',
        ];
    }

    protected static function newFactory(): BackofficeAgentFactory
    {
        return BackofficeAgentFactory::new();
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(BackofficeRefreshToken::class);
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
