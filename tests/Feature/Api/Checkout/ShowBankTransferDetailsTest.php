<?php

namespace Tests\Feature\Api\Checkout;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TestDataHelper;
use Tests\TestCase;

class ShowBankTransferDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_site_aware_bank_transfer_details_from_config(): void
    {
        [, $siteDomain] = TestDataHelper::seedSite('fr');

        config()->set('payments.bank_transfer_details.fr', [
            'account_holder' => 'NutriSport France',
            'iban' => 'FR7612345678901234567890123',
            'bic' => 'AGRIFRPP',
            'bank_name' => 'Banque NutriSport',
        ]);

        $this->getJson("http://{$siteDomain}/v1/bank-transfer-details")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'account_holder' => 'NutriSport France',
                    'iban' => 'FR7612345678901234567890123',
                    'bic' => 'AGRIFRPP',
                    'bank_name' => 'Banque NutriSport',
                ],
            ]);
    }
}
