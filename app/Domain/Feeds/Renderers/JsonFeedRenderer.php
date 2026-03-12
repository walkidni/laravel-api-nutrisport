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

    /**
     * @param array<int, FeedProductDTO> $products
     *
     * @return array{products: array<int, array{id:int, name:string, in_stock:bool}>}
     */
    public function render(array $products): array
    {
        return [
            'products' => array_map(
                static fn (FeedProductDTO $product): array => $product->toArray(),
                $products,
            ),
        ];
    }
}
