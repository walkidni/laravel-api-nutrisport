<?php

namespace App\Domain\Orders\Enums;

enum PaymentMethodEnum: string
{
    case BANK_TRANSFER = 'BANK_TRANSFER';
}
