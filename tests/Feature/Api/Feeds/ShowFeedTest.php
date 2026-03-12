<?php

namespace Tests\Feature\Api\Feeds;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShowFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_json_feed_for_the_resolved_site(): void
    {
        [$siteDomain, $productId] = $this->seedFeedProductForSite('fr');

        $response = $this->getJson("http://{$siteDomain}/v1/feeds/json");

        $response
            ->assertOk()
            ->assertExactJson([
                'products' => [
                    [
                        'id' => $productId,
                        'name' => 'Whey Protein',
                        'in_stock' => true,
                    ],
                ],
            ]);

        $this->assertStringStartsWith(
            'application/json',
            (string) $response->headers->get('content-type'),
        );
    }

    public function test_returns_the_xml_feed_for_the_resolved_site(): void
    {
        [$siteDomain, $productId] = $this->seedFeedProductForSite('fr');

        $response = $this->get("http://{$siteDomain}/v1/feeds/xml");

        $response->assertOk();

        $this->assertStringStartsWith(
            'application/xml',
            (string) $response->headers->get('content-type'),
        );

        $document = new DOMDocument();
        $this->assertTrue($document->loadXML((string) $response->getContent()));

        $products = $document->getElementsByTagName('product');

        $this->assertSame(1, $products->count());
        $this->assertSame((string) $productId, $products->item(0)?->getElementsByTagName('id')->item(0)?->textContent);
        $this->assertSame('Whey Protein', $products->item(0)?->getElementsByTagName('name')->item(0)?->textContent);
        $this->assertSame('true', $products->item(0)?->getElementsByTagName('in_stock')->item(0)?->textContent);
    }

    public function test_returns_not_found_for_an_unsupported_feed_format(): void
    {
        [$siteDomain] = $this->seedFeedProductForSite('fr');

        $this->getJson("http://{$siteDomain}/v1/feeds/csv")
            ->assertNotFound();
    }

    /**
     * @return array{string, int}
     */
    private function seedFeedProductForSite(string $siteCode): array
    {
        $siteDomain = (string) config("sites.domains.{$siteCode}");

        $siteId = DB::table('sites')->insertGetId([
            Site::CODE => $siteCode,
            Site::DOMAIN => $siteDomain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            Product::NAME => 'Whey Protein',
            Product::STOCK => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_site_prices')->insert([
            ProductSitePrice::PRODUCT_ID => $productId,
            ProductSitePrice::SITE_ID => $siteId,
            ProductSitePrice::PRICE_AMOUNT_CENTS => 2999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$siteDomain, $productId];
    }
}
