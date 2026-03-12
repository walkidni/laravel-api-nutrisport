<?php

namespace App\Domain\Feeds\Support;

final class FeedFormatRegistry
{
    /**
     * @return array<int, string>
     */
    public function formats(): array
    {
        return [
            'json',
            'xml',
        ];
    }
}
