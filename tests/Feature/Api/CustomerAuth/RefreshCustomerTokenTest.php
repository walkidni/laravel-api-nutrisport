<?php

namespace Tests\Feature\Api\CustomerAuth;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerRefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\Support\TestDataHelper;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class RefreshCustomerTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_refreshes_a_customer_session_and_rotates_the_refresh_token(): void
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

        $oldRefreshToken = (string) $loginResponse->json('data.refresh_token');

        $response = $this->postJson("http://{$siteDomain}/v1/auth/refresh", [
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

        $this->assertSame($siteId, (int) $payload->get(Customer::SITE_ID));
        $this->assertSame('fr', (string) $payload->get('code'));

        $oldTokenRecord = CustomerRefreshToken::query()
            ->where(CustomerRefreshToken::TOKEN_HASH, hash('sha256', $oldRefreshToken))
            ->sole();

        $this->assertNotNull($oldTokenRecord->getAttribute(CustomerRefreshToken::REVOKED_AT));

        $newTokenRecord = CustomerRefreshToken::query()
            ->where(CustomerRefreshToken::TOKEN_HASH, hash('sha256', $newRefreshToken))
            ->sole();

        $this->assertNull($newTokenRecord->getAttribute(CustomerRefreshToken::REVOKED_AT));
        $this->assertSame(
            $oldTokenRecord->getAttribute(CustomerRefreshToken::ABSOLUTE_EXPIRES_AT)?->toIso8601String(),
            $newTokenRecord->getAttribute(CustomerRefreshToken::ABSOLUTE_EXPIRES_AT)?->toIso8601String(),
        );
    }

    public function test_rejects_using_a_refresh_token_after_it_has_been_rotated(): void
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

        $oldRefreshToken = (string) $loginResponse->json('data.refresh_token');

        $this->postJson("http://{$siteDomain}/v1/auth/refresh", [
            'refresh_token' => $oldRefreshToken,
        ])->assertOk();

        $response = $this->postJson("http://{$siteDomain}/v1/auth/refresh", [
            'refresh_token' => $oldRefreshToken,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    }

    public function test_rejects_refreshing_a_customer_session_on_a_different_site(): void
    {
        [$frSiteId, $frSiteDomain] = TestDataHelper::seedSite('fr');
        [, $itSiteDomain] = TestDataHelper::seedSite('it');

        Customer::factory()->create([
            Customer::SITE_ID => $frSiteId,
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => Hash::make('secret-password'),
        ]);

        $loginResponse = $this->postJson("http://{$frSiteDomain}/v1/auth/login", [
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => 'secret-password',
        ]);

        $refreshToken = (string) $loginResponse->json('data.refresh_token');

        $response = $this->postJson("http://{$itSiteDomain}/v1/auth/refresh", [
            'refresh_token' => $refreshToken,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('refresh_token');
    }

    public function test_rejects_refreshing_after_the_absolute_session_cap(): void
    {
        config()->set('auth.customers.refresh_token_ttl', 120);
        config()->set('auth.customers.absolute_session_ttl', 60);

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

        Carbon::setTestNow(now()->addMinutes(61));

        try {
            $response = $this->postJson("http://{$siteDomain}/v1/auth/refresh", [
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
