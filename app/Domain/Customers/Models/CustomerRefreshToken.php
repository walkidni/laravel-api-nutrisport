<?php

namespace App\Domain\Customers\Models;

use App\Domain\Shared\SiteContext\Site;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRefreshToken extends Model
{
    public const ID = 'id';
    public const CUSTOMER_ID = 'customer_id';
    public const SITE_ID = 'site_id';
    public const TOKEN_HASH = 'token_hash';
    public const ISSUED_AT = 'issued_at';
    public const EXPIRES_AT = 'expires_at';
    public const ABSOLUTE_EXPIRES_AT = 'absolute_expires_at';
    public const REVOKED_AT = 'revoked_at';

    protected $fillable = [
        self::CUSTOMER_ID,
        self::SITE_ID,
        self::TOKEN_HASH,
        self::ISSUED_AT,
        self::EXPIRES_AT,
        self::ABSOLUTE_EXPIRES_AT,
        self::REVOKED_AT,
    ];

    protected function casts(): array
    {
        return [
            self::ISSUED_AT => 'datetime',
            self::EXPIRES_AT => 'datetime',
            self::ABSOLUTE_EXPIRES_AT => 'datetime',
            self::REVOKED_AT => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
