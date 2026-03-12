<?php

namespace Tests\Feature\Api\Backoffice;

use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Domain\Backoffice\Models\BackofficeRefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogoutBackofficeSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_logs_out_the_current_backoffice_session_only_and_revokes_that_refresh_token(): void
    {
        BackofficeAgent::factory()->create([
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => Hash::make('secret-password'),
        ]);

        $firstLoginResponse = $this->postJson('/v1/backoffice/auth/login', [
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => 'secret-password',
        ]);

        $secondLoginResponse = $this->postJson('/v1/backoffice/auth/login', [
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => 'secret-password',
        ]);

        $firstRefreshToken = (string) $firstLoginResponse->json('data.refresh_token');
        $secondRefreshToken = (string) $secondLoginResponse->json('data.refresh_token');

        $this->postJson('/v1/backoffice/auth/logout', [
            'refresh_token' => $firstRefreshToken,
        ])->assertNoContent();

        $revokedTokenRecord = BackofficeRefreshToken::query()
            ->where(BackofficeRefreshToken::TOKEN_HASH, hash('sha256', $firstRefreshToken))
            ->sole();

        $activeTokenRecord = BackofficeRefreshToken::query()
            ->where(BackofficeRefreshToken::TOKEN_HASH, hash('sha256', $secondRefreshToken))
            ->sole();

        $this->assertNotNull($revokedTokenRecord->getAttribute(BackofficeRefreshToken::REVOKED_AT));
        $this->assertNull($activeTokenRecord->getAttribute(BackofficeRefreshToken::REVOKED_AT));

        $this->postJson('/v1/backoffice/auth/refresh', [
            'refresh_token' => $firstRefreshToken,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');

        $this->postJson('/v1/backoffice/auth/refresh', [
            'refresh_token' => $secondRefreshToken,
        ])->assertOk();
    }

    public function test_logout_is_idempotent_for_an_already_consumed_refresh_token(): void
    {
        BackofficeAgent::factory()->create([
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => Hash::make('secret-password'),
        ]);

        $loginResponse = $this->postJson('/v1/backoffice/auth/login', [
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => 'secret-password',
        ]);

        $refreshToken = (string) $loginResponse->json('data.refresh_token');

        $this->postJson('/v1/backoffice/auth/logout', [
            'refresh_token' => $refreshToken,
        ])->assertNoContent();

        $this->postJson('/v1/backoffice/auth/logout', [
            'refresh_token' => $refreshToken,
        ])->assertNoContent();

        $this->postJson('/v1/backoffice/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    }
}
