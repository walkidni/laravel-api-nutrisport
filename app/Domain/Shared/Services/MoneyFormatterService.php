<?php

namespace App\Domain\Shared\Services;

final class MoneyFormatterService
{
    public function formatCents(int $amountCents): string
    {
        $absoluteAmountCents = abs($amountCents);
        $wholeUnits = intdiv($absoluteAmountCents, 100);
        $fractionalUnits = $absoluteAmountCents % 100;
        $sign = $amountCents < 0 ? '-' : '';

        return sprintf('%s%d.%02d', $sign, $wholeUnits, $fractionalUnits);
    }
}
