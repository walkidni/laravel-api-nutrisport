<?php

namespace App\Domain\Orders\Enums;

enum OrderStatusEnum: string
{
    case PENDING_PAYMENT = 'PENDING_PAYMENT';
}
