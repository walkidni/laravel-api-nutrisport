<?php

namespace Database\Seeders;

use App\Domain\Shared\SiteContext\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();
        $sites = collect(config('sites.domains'))
            ->map(fn (string $domain, string $code): array => [
                Site::CODE => $code,
                Site::DOMAIN => $domain,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->values()
            ->all();

        DB::table('sites')->upsert($sites, [Site::CODE], [Site::DOMAIN, 'updated_at']);
    }
}
