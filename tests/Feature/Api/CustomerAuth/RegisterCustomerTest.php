<?php

namespace Tests\Feature\Api\CustomerAuth;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterCustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_a_customer_for_the_resolved_site_without_issuing_tokens(): void
    {
        [$siteId, $siteDomain] = $this->seedSite('fr');

        $response = $this->postJson("http://{$siteDomain}/v1/auth/register", [
            'email' => 'customer@example.com',
            'password' => 'secret-password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonMissingPath('data.access_token')
            ->assertJsonMissingPath('data.refresh_token');

        $customer = DB::table('customers')
            ->where(Customer::EMAIL, 'customer@example.com')
            ->first();

        $this->assertNotNull($customer);
        $this->assertSame('customer@example.com', $customer->{Customer::EMAIL});
        $this->assertSame($siteId, $customer->{Customer::SITE_ID});
        $this->assertTrue(Hash::check('secret-password', $customer->{Customer::PASSWORD}));
    }

    /**
     * @return array{int, string}
     */
    private function seedSite(string $siteCode): array
    {
        $siteDomain = (string) config("sites.domains.{$siteCode}");

        $siteId = DB::table('sites')->insertGetId([
            Site::CODE => $siteCode,
            Site::DOMAIN => $siteDomain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$siteId, $siteDomain];
    }
}
