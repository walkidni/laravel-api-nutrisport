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
            ->assertJsonPath('data.email', 'customer@example.com')
            ->assertJsonMissingPath('data.access_token')
            ->assertJsonMissingPath('data.refresh_token')
            ->assertJsonMissingPath('data.password');

        $customer = DB::table('customers')
            ->where(Customer::EMAIL, 'customer@example.com')
            ->first();

        $this->assertNotNull($customer);
        $this->assertSame('customer@example.com', $customer->{Customer::EMAIL});
        $this->assertSame($siteId, $customer->{Customer::SITE_ID});
        $this->assertTrue(Hash::check('secret-password', $customer->{Customer::PASSWORD}));
    }

    public function test_rejects_registering_the_same_email_on_the_same_site(): void
    {
        [$siteId, $siteDomain] = $this->seedSite('fr');

        DB::table('customers')->insert([
            Customer::SITE_ID => $siteId,
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => Hash::make('existing-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("http://{$siteDomain}/v1/auth/register", [
            'email' => 'customer@example.com',
            'password' => 'secret-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertSame(
            1,
            DB::table('customers')
                ->where(Customer::SITE_ID, $siteId)
                ->where(Customer::EMAIL, 'customer@example.com')
                ->count(),
        );
    }

    public function test_allows_registering_the_same_email_on_a_different_site(): void
    {
        [$frSiteId, $frSiteDomain] = $this->seedSite('fr');
        [$itSiteId, $itSiteDomain] = $this->seedSite('it');

        DB::table('customers')->insert([
            Customer::SITE_ID => $frSiteId,
            Customer::EMAIL => 'customer@example.com',
            Customer::PASSWORD => Hash::make('existing-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("http://{$itSiteDomain}/v1/auth/register", [
            'email' => 'customer@example.com',
            'password' => 'secret-password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', 'customer@example.com')
            ->assertJsonMissingPath('data.access_token')
            ->assertJsonMissingPath('data.refresh_token');

        $this->assertSame(
            1,
            DB::table('customers')
                ->where(Customer::SITE_ID, $frSiteId)
                ->where(Customer::EMAIL, 'customer@example.com')
                ->count(),
        );

        $this->assertSame(
            1,
            DB::table('customers')
                ->where(Customer::SITE_ID, $itSiteId)
                ->where(Customer::EMAIL, 'customer@example.com')
                ->count(),
        );
    }

    public function test_translates_a_same_site_duplicate_key_conflict_into_a_validation_error(): void
    {
        [$siteId, $siteDomain] = $this->seedSite('fr');

        Customer::creating(function (Customer $customer) use ($siteId): void {
            static $conflictInserted = false;

            if ($conflictInserted) {
                return;
            }

            $conflictInserted = true;

            DB::table('customers')->insert([
                Customer::SITE_ID => $siteId,
                Customer::EMAIL => (string) $customer->getAttribute(Customer::EMAIL),
                Customer::PASSWORD => Hash::make('existing-password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $response = $this->postJson("http://{$siteDomain}/v1/auth/register", [
            'email' => 'customer@example.com',
            'password' => 'secret-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(Customer::EMAIL);

        $this->assertSame(
            1,
            DB::table('customers')
                ->where(Customer::SITE_ID, $siteId)
                ->where(Customer::EMAIL, 'customer@example.com')
                ->count(),
        );
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
