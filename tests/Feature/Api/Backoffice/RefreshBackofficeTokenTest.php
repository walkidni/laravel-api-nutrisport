<?php

namespace Tests\Feature\Api\Backoffice;

use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Domain\Backoffice\Models\BackofficeRefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class RefreshBackofficeTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_refreshes_a_backoffice_session_and_rotates_the_refresh_token(): void
    {
        $agent = BackofficeAgent::factory()->create([
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => Hash::make('secret-password'),
        ]);

        $loginResponse = $this->postJson('/v1/backoffice/auth/login', [
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => 'secret-password',
        ]);

        $oldRefreshToken = (string) $loginResponse->json('data.refresh_token');

        $response = $this->postJson('/v1/backoffice/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                ],
            ]);

        $newAccessToken = (string) $response->json('data.access_token');
        $newRefreshToken = (string) $response->json('data.refresh_token');

        $this->assertNotSame('', $newAccessToken);
        $this->assertNotSame('', $newRefreshToken);
        $this->assertNotSame($oldRefreshToken, $newRefreshToken);

        $payload = JWTAuth::setToken($newAccessToken)->getPayload();

        $this->assertSame((string) $agent->getKey(), (string) $payload->get('sub'));

        $oldTokenRecord = BackofficeRefreshToken::query()
            ->where(BackofficeRefreshToken::TOKEN_HASH, hash('sha256', $oldRefreshToken))
            ->sole();

        $this->assertNotNull($oldTokenRecord->getAttribute(BackofficeRefreshToken::REVOKED_AT));

        $newTokenRecord = BackofficeRefreshToken::query()
            ->where(BackofficeRefreshToken::TOKEN_HASH, hash('sha256', $newRefreshToken))
            ->sole();

        $this->assertNull($newTokenRecord->getAttribute(BackofficeRefreshToken::REVOKED_AT));
        $this->assertSame(
            $oldTokenRecord->getAttribute(BackofficeRefreshToken::ABSOLUTE_EXPIRES_AT)?->toIso8601String(),
            $newTokenRecord->getAttribute(BackofficeRefreshToken::ABSOLUTE_EXPIRES_AT)?->toIso8601String(),
        );
    }

    public function test_rejects_using_a_refresh_token_after_it_has_been_rotated(): void
    {
        BackofficeAgent::factory()->create([
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => Hash::make('secret-password'),
        ]);

        $loginResponse = $this->postJson('/v1/backoffice/auth/login', [
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => 'secret-password',
        ]);

        $oldRefreshToken = (string) $loginResponse->json('data.refresh_token');

        $this->postJson('/v1/backoffice/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ])->assertOk();

        $response = $this->postJson('/v1/backoffice/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    }

    public function test_rejects_refreshing_after_the_absolute_session_cap(): void
    {
        config()->set('auth.backoffice.refresh_token_ttl', 600);
        config()->set('auth.backoffice.absolute_session_ttl', 480);

        BackofficeAgent::factory()->create([
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => Hash::make('secret-password'),
        ]);

        $loginResponse = $this->postJson('/v1/backoffice/auth/login', [
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => 'secret-password',
        ]);

        $refreshToken = (string) $loginResponse->json('data.refresh_token');

        Carbon::setTestNow(now()->addMinutes(481));

        try {
            $response = $this->postJson('/v1/backoffice/auth/refresh', [
                'refresh_token' => $refreshToken,
            ]);
        } finally {
            Carbon::setTestNow();
        }

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    }
}
