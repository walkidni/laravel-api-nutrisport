<?php

namespace Tests\Feature\Api\Cart;

use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShowCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_an_empty_cart_when_no_cart_exists(): void
    {
        $siteDomain = (string) config('sites.domains.fr');

        DB::table('sites')->insert([
            Site::CODE => 'fr',
            Site::DOMAIN => $siteDomain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson("http://{$siteDomain}/v1/cart")
            ->assertOk()
            ->assertHeaderMissing('X-Cart-Token')
            ->assertExactJson([
                'data' => [
                    'lines' => [],
                    'item_count' => 0,
                    'total_amount' => 0,
                ],
            ]);
    }
}
