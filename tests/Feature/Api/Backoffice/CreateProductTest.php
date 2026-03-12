<?php

namespace Tests\Feature\Api\Backoffice;

use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Support\TestDataHelper;
use Tests\TestCase;
use Tymon\JWTAuth\JWTGuard;

class CreateProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_creates_a_product_with_initial_stock_and_site_prices(): void
    {
        [$frSiteId] = TestDataHelper::seedSite('fr');
        [$itSiteId] = TestDataHelper::seedSite('it');

        $agent = BackofficeAgent::factory()->create([
            BackofficeAgent::CAN_CREATE_PRODUCTS => true,
        ]);

        $response = $this->withToken($this->issueBackofficeAccessToken($agent))
            ->postJson('/v1/backoffice/products', [
                'name' => 'Creatine Monohydrate',
                'initial_stock' => 25,
                'site_prices' => [
                    [
                        'site_code' => 'fr',
                        'price' => '29.99',
                    ],
                    [
                        'site_code' => 'it',
                        'price' => '31.50',
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'stock',
                    'site_prices' => [
                        '*' => [
                            'site_code',
                            'price_amount',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.name', 'Creatine Monohydrate')
            ->assertJsonPath('data.stock', 25)
            ->assertJsonPath('data.site_prices.0.site_code', 'fr')
            ->assertJsonPath('data.site_prices.0.price_amount', '29.99')
            ->assertJsonPath('data.site_prices.1.site_code', 'it')
            ->assertJsonPath('data.site_prices.1.price_amount', '31.50');

        $product = Product::query()->sole();

        $this->assertSame('Creatine Monohydrate', $product->getAttribute(Product::NAME));
        $this->assertSame(25, $product->getAttribute(Product::STOCK));

        $this->assertDatabaseHas('product_site_prices', [
            ProductSitePrice::PRODUCT_ID => $product->getKey(),
            ProductSitePrice::SITE_ID => $frSiteId,
            ProductSitePrice::PRICE_AMOUNT_CENTS => 2999,
        ]);

        $this->assertDatabaseHas('product_site_prices', [
            ProductSitePrice::PRODUCT_ID => $product->getKey(),
            ProductSitePrice::SITE_ID => $itSiteId,
            ProductSitePrice::PRICE_AMOUNT_CENTS => 3150,
        ]);
    }

    public function test_forbids_an_agent_without_product_creation_permission(): void
    {
        TestDataHelper::seedSite('fr');

        $agent = BackofficeAgent::factory()->create([
            BackofficeAgent::CAN_CREATE_PRODUCTS => false,
        ]);

        $this->withToken($this->issueBackofficeAccessToken($agent))
            ->postJson('/v1/backoffice/products', [
                'name' => 'Creatine Monohydrate',
                'initial_stock' => 25,
                'site_prices' => [
                    [
                        'site_code' => 'fr',
                        'price' => '29.99',
                    ],
                ],
            ])
            ->assertForbidden();
    }

    public function test_agent_id_one_bypasses_the_product_creation_permission_check(): void
    {
        TestDataHelper::seedSite('fr');

        $agent = BackofficeAgent::factory()->create([
            BackofficeAgent::ID => 1,
            BackofficeAgent::CAN_CREATE_PRODUCTS => false,
        ]);

        $this->assertSame(1, (int) $agent->getKey());

        $this->withToken($this->issueBackofficeAccessToken($agent))
            ->postJson('/v1/backoffice/products', [
                'name' => 'Creatine Monohydrate',
                'initial_stock' => 25,
                'site_prices' => [
                    [
                        'site_code' => 'fr',
                        'price' => '29.99',
                    ],
                ],
            ])
            ->assertCreated();
    }

    private function issueBackofficeAccessToken(BackofficeAgent $agent): string
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('backoffice');

        return $guard->login($agent);
    }
}
