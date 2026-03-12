<?php

namespace App\Domain\Backoffice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackofficeRefreshToken extends Model
{
    public const ID = 'id';
    public const BACKOFFICE_AGENT_ID = 'backoffice_agent_id';
    public const TOKEN_HASH = 'token_hash';
    public const ISSUED_AT = 'issued_at';
    public const EXPIRES_AT = 'expires_at';
    public const ABSOLUTE_EXPIRES_AT = 'absolute_expires_at';
    public const REVOKED_AT = 'revoked_at';

    protected $fillable = [
        self::BACKOFFICE_AGENT_ID,
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

    public function agent(): BelongsTo
    {
        return $this->belongsTo(BackofficeAgent::class, self::BACKOFFICE_AGENT_ID);
    }
}
