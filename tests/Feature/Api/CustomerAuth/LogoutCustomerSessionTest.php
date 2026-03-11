<?php

namespace Tests\Feature\Api\CustomerAuth;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerRefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\TestDataHelper;
use Tests\TestCase;

class LogoutCustomerSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_logs_out_the_current_customer_session_only_and_revokes_that_refresh_token(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');

        Customer::factory()->create([
            Customer::SITE_ID => $siteId,
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => Hash::make('secret-password'),
        ]);

        $firstLoginResponse = $this->postJson("http://{$siteDomain}/v1/auth/login", [
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => 'secret-password',
        ]);

        $secondLoginResponse = $this->postJson("http://{$siteDomain}/v1/auth/login", [
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => 'secret-password',
        ]);

        $firstRefreshToken = (string) $firstLoginResponse->json('data.refresh_token');
        $secondRefreshToken = (string) $secondLoginResponse->json('data.refresh_token');

        $this->postJson("http://{$siteDomain}/v1/auth/logout", [
            'refresh_token' => $firstRefreshToken,
        ])->assertNoContent();

        $revokedTokenRecord = CustomerRefreshToken::query()
            ->where(CustomerRefreshToken::TOKEN_HASH, hash('sha256', $firstRefreshToken))
            ->sole();

        $activeTokenRecord = CustomerRefreshToken::query()
            ->where(CustomerRefreshToken::TOKEN_HASH, hash('sha256', $secondRefreshToken))
            ->sole();

        $this->assertNotNull($revokedTokenRecord->getAttribute(CustomerRefreshToken::REVOKED_AT));
        $this->assertNull($activeTokenRecord->getAttribute(CustomerRefreshToken::REVOKED_AT));

        $this->postJson("http://{$siteDomain}/v1/auth/refresh", [
            'refresh_token' => $firstRefreshToken,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');

        $this->postJson("http://{$siteDomain}/v1/auth/refresh", [
            'refresh_token' => $secondRefreshToken,
        ])->assertOk();
    }

    public function test_logout_is_idempotent_for_an_already_consumed_refresh_token(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');

        Customer::factory()->create([
            Customer::SITE_ID => $siteId,
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => Hash::make('secret-password'),
        ]);

        $loginResponse = $this->postJson("http://{$siteDomain}/v1/auth/login", [
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => 'secret-password',
        ]);

        $refreshToken = (string) $loginResponse->json('data.refresh_token');

        $this->postJson("http://{$siteDomain}/v1/auth/logout", [
            'refresh_token' => $refreshToken,
        ])->assertNoContent();

        $this->postJson("http://{$siteDomain}/v1/auth/logout", [
            'refresh_token' => $refreshToken,
        ])->assertNoContent();

        $this->postJson("http://{$siteDomain}/v1/auth/refresh", [
            'refresh_token' => $refreshToken,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    }
}
