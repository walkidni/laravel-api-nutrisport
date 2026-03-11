<?php

return [
    'bank_transfer_details' => [
        'fr' => [
            'account_holder' => env('PAYMENTS_FR_BANK_TRANSFER_ACCOUNT_HOLDER', 'NutriSport France'),
            'iban' => env('PAYMENTS_FR_BANK_TRANSFER_IBAN', 'FR7612345678901234567890123'),
            'bic' => env('PAYMENTS_FR_BANK_TRANSFER_BIC', 'AGRIFRPP'),
            'bank_name' => env('PAYMENTS_FR_BANK_TRANSFER_BANK_NAME', 'Banque NutriSport France'),
        ],
        'it' => [
            'account_holder' => env('PAYMENTS_IT_BANK_TRANSFER_ACCOUNT_HOLDER', 'NutriSport Italia'),
            'iban' => env('PAYMENTS_IT_BANK_TRANSFER_IBAN', 'IT60X0542811101000000123456'),
            'bic' => env('PAYMENTS_IT_BANK_TRANSFER_BIC', 'BCITITMM'),
            'bank_name' => env('PAYMENTS_IT_BANK_TRANSFER_BANK_NAME', 'Banca NutriSport Italia'),
        ],
        'be' => [
            'account_holder' => env('PAYMENTS_BE_BANK_TRANSFER_ACCOUNT_HOLDER', 'NutriSport Belgique'),
            'iban' => env('PAYMENTS_BE_BANK_TRANSFER_IBAN', 'BE68539007547034'),
            'bic' => env('PAYMENTS_BE_BANK_TRANSFER_BIC', 'KREDBEBB'),
            'bank_name' => env('PAYMENTS_BE_BANK_TRANSFER_BANK_NAME', 'Banque NutriSport Belgique'),
        ],
    ],
];
