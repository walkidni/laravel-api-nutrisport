<?php

namespace App\Domain\Feeds\DTOs;

final readonly class RenderedFeedDTO
{
    public function __construct(
        public string $body,
        public string $contentType,
    ) {
    }
}
