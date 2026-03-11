<?php

namespace Tests\Feature\Api\CustomerAuth;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerRefreshToken;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\TestDataHelper;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_logs_in_a_customer_for_the_resolved_site_and_returns_tokens(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => Hash::make('secret-password'),
        ]);

        $response = $this->postJson("http://{$siteDomain}/v1/auth/login", [
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => 'secret-password',
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

        $this->assertSame((string) $customer->getKey(), (string) $payload->get('sub'));
        $this->assertSame($siteId, (int) $payload->get(Customer::SITE_ID));
        $this->assertSame('fr', (string) $payload->get(Site::CODE));

        $refreshTokenRecord = CustomerRefreshToken::query()->sole();

        $this->assertSame($customer->getKey(), $refreshTokenRecord->getAttribute(CustomerRefreshToken::CUSTOMER_ID));
        $this->assertSame($siteId, $refreshTokenRecord->getAttribute(CustomerRefreshToken::SITE_ID));
        $this->assertSame(hash('sha256', $refreshToken), $refreshTokenRecord->getAttribute(CustomerRefreshToken::TOKEN_HASH));
        $this->assertNotSame($refreshToken, $refreshTokenRecord->getAttribute(CustomerRefreshToken::TOKEN_HASH));
    }

    public function test_rejects_logging_in_with_valid_credentials_on_a_different_site(): void
    {
        [$frSiteId] = TestDataHelper::seedSite('fr');
        [, $itSiteDomain] = TestDataHelper::seedSite('it');

        Customer::factory()->create([
            Customer::SITE_ID => $frSiteId,
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => Hash::make('secret-password'),
        ]);

        $response = $this->postJson("http://{$itSiteDomain}/v1/auth/login", [
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => 'secret-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(Customer::EMAIL);

        $this->assertDatabaseCount('customer_refresh_tokens', 0);
    }
}
