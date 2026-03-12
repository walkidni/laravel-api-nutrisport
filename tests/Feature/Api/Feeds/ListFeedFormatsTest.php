<?php

namespace Tests\Feature\Api\Feeds;

use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListFeedFormatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_absolute_feed_links_for_the_resolved_site(): void
    {
        $siteDomain = $this->seedSite('fr');

        $this->getJson("http://{$siteDomain}/v1/feeds")
            ->assertOk()
            ->assertExactJson([
                'formats' => [
                    [
                        'format' => 'json',
                        'url' => "http://{$siteDomain}/v1/feeds/json",
                    ],
                    [
                        'format' => 'xml',
                        'url' => "http://{$siteDomain}/v1/feeds/xml",
                    ],
                ],
            ]);
    }

    private function seedSite(string $siteCode): string
    {
        $siteDomain = (string) config("sites.domains.{$siteCode}");

        DB::table('sites')->insert([
            Site::CODE => $siteCode,
            Site::DOMAIN => $siteDomain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $siteDomain;
    }
}
