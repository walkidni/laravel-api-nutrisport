<?php

namespace Tests\Unit\Domain\Customers;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerRefreshToken;
use App\Domain\Customers\Services\CustomerRefreshTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\TestDataHelper;
use Tests\TestCase;

class CustomerRefreshTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumes_a_refresh_token_only_once_for_a_site(): void
    {
        [$siteId] = TestDataHelper::seedSite('fr');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => Hash::make('secret-password'),
        ]);

        $service = app(CustomerRefreshTokenService::class);
        [, $plainToken] = $service->issue($customer, $customer->site()->firstOrFail());

        $firstConsumed = DB::transaction(
            fn () => $service->consumeForSite($plainToken, $siteId),
        );

        $secondConsumed = DB::transaction(
            fn () => $service->consumeForSite($plainToken, $siteId),
        );

        $this->assertInstanceOf(CustomerRefreshToken::class, $firstConsumed);
        $this->assertNotNull($firstConsumed->getAttribute(CustomerRefreshToken::REVOKED_AT));
        $this->assertNull($secondConsumed);
    }
}
