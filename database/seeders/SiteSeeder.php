<?php

namespace Database\Seeders;

use App\Domain\Shared\SiteContext\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sites')->upsert([
            [
                Site::CODE => 'fr',
                Site::DOMAIN => 'nutri-sport.fr',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                Site::CODE => 'it',
                Site::DOMAIN => 'nutri-sport.it',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                Site::CODE => 'be',
                Site::DOMAIN => 'nutri-sport.be',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], [Site::CODE], [Site::DOMAIN, 'updated_at']);
    }
}
