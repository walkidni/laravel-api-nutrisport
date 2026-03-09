<?php

namespace Tests\Feature\Api\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_products_for_the_resolved_site(): void
    {
        $siteId = DB::table('sites')->insertGetId([
            'code' => 'fr',
            'domain' => 'nutri-sport.fr',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'name' => 'Whey Protein',
            'stock' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_site_prices')->insert([
            'product_id' => $productId,
            'site_id' => $siteId,
            'price_amount' => 2999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('Host', 'nutri-sport.fr')
            ->getJson('/products')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    [
                        'id' => $productId,
                        'name' => 'Whey Protein',
                        'price_amount' => 2999,
                        'in_stock' => true,
                    ],
                ],
            ]);
    }
}
