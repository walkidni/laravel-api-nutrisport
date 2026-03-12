<?php

namespace App\Domain\Feeds\Renderers;

use App\Domain\Feeds\Contracts\FeedRenderer;
use App\Domain\Feeds\DTOs\FeedProductDTO;

final class JsonFeedRenderer implements FeedRenderer
{
    public function format(): string
    {
        return 'json';
    }

    public function contentType(): string
    {
        return 'application/json';
    }

    /**
     * @param array<int, FeedProductDTO> $products
     */
    public function render(array $products): string
    {
        return (string) json_encode([
            'products' => array_map(
                static fn (FeedProductDTO $product): array => $product->toArray(),
                $products,
            ),
        ], JSON_THROW_ON_ERROR);
    }
}
