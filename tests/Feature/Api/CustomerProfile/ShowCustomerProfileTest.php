<?php

namespace Tests\Feature\Api\CustomerProfile;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Support\TestDataHelper;
use Tests\TestCase;
use Tymon\JWTAuth\JWTGuard;

class ShowCustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_returns_the_authenticated_customer_profile_for_the_resolved_site(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
            Customer::EMAIL => 'customer@example.com',
        ]);

        $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->getJson("http://{$siteDomain}/v1/me")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ],
            ])
            ->assertJsonPath('data.id', $customer->getKey())
            ->assertJsonPath('data.first_name', $customer->getAttribute(Customer::FIRST_NAME))
            ->assertJsonPath('data.last_name', $customer->getAttribute(Customer::LAST_NAME))
            ->assertJsonPath('data.email', 'customer@example.com');
    }

    public function test_rejects_showing_the_profile_on_a_different_resolved_site(): void
    {
        [$siteId] = TestDataHelper::seedSite('fr');
        [, $otherSiteDomain] = TestDataHelper::seedSite('it');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
            Customer::EMAIL => 'customer@example.com',
        ]);

        $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->getJson("http://{$otherSiteDomain}/v1/me")
            ->assertForbidden();
    }

    public function test_updates_the_authenticated_customer_profile_with_partial_fields(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
            Customer::FIRST_NAME => 'Alice',
            Customer::LAST_NAME => 'Runner',
            Customer::EMAIL => 'customer@example.com',
        ]);

        $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->patchJson("http://{$siteDomain}/v1/me", [
                Customer::FIRST_NAME => 'Alicia',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $customer->getKey())
            ->assertJsonPath('data.first_name', 'Alicia')
            ->assertJsonPath('data.last_name', 'Runner')
            ->assertJsonPath('data.email', 'customer@example.com');

        $customer->refresh();

        $this->assertSame('Alicia', $customer->getAttribute(Customer::FIRST_NAME));
        $this->assertSame('Runner', $customer->getAttribute(Customer::LAST_NAME));
        $this->assertSame('customer@example.com', $customer->getAttribute(Customer::EMAIL));
    }

    public function test_rejects_updating_the_email_to_one_already_used_on_the_same_site(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
            Customer::EMAIL => 'customer@example.com',
        ]);

        Customer::factory()->create([
            Customer::SITE_ID => $siteId,
            Customer::EMAIL => 'taken@example.com',
        ]);

        $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->patchJson("http://{$siteDomain}/v1/me", [
                Customer::EMAIL => 'taken@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(Customer::EMAIL);
    }

    private function issueCustomerAccessToken(Customer $customer, int $siteId): string
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('customer');

        return $guard
            ->claims([
                Customer::SITE_ID => $siteId,
                Site::CODE => 'fr',
            ])
            ->login($customer);
    }
}
