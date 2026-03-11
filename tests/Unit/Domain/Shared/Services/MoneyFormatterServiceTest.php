<?php

namespace Tests\Unit\Domain\Shared\Services;

use App\Domain\Shared\Services\MoneyFormatterService;
use Tests\TestCase;

class MoneyFormatterServiceTest extends TestCase
{
    public function test_formats_integer_cents_as_fixed_scale_decimal_strings(): void
    {
        $service = app(MoneyFormatterService::class);

        $this->assertSame('0.00', $service->formatCents(0));
        $this->assertSame('29.99', $service->formatCents(2999));
        $this->assertSame('1234.56', $service->formatCents(123456));
    }
}
