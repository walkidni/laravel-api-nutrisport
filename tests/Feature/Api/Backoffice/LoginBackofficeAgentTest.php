<?php

namespace Tests\Feature\Api\Backoffice;

use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Domain\Backoffice\Models\BackofficeRefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginBackofficeAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_logs_in_a_backoffice_agent_with_email_and_password(): void
    {
        $agent = BackofficeAgent::factory()->create([
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => Hash::make('secret-password'),
        ]);

        $response = $this->postJson('/v1/backoffice/auth/login', [
            BackofficeAgent::EMAIL => 'agent@example.com',
            BackofficeAgent::PASSWORD => 'secret-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                ],
            ]);

        $accessToken = (string) $response->json('data.access_token');
        $refreshToken = (string) $response->json('data.refresh_token');

        $this->assertNotSame('', $accessToken);
        $this->assertNotSame('', $refreshToken);

        $payload = JWTAuth::setToken($accessToken)->getPayload();

        $this->assertSame((string) $agent->getKey(), (string) $payload->get('sub'));

        $refreshTokenRecord = BackofficeRefreshToken::query()->sole();

        $this->assertSame($agent->getKey(), $refreshTokenRecord->getAttribute(BackofficeRefreshToken::BACKOFFICE_AGENT_ID));
        $this->assertSame(hash('sha256', $refreshToken), $refreshTokenRecord->getAttribute(BackofficeRefreshToken::TOKEN_HASH));
        $this->assertNotSame($refreshToken, $refreshTokenRecord->getAttribute(BackofficeRefreshToken::TOKEN_HASH));
    }
}
