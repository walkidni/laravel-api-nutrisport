<?php

namespace App\Domain\Feeds\Contracts;

use App\Domain\Feeds\DTOs\FeedProductDTO;

interface FeedRenderer
{
    public function format(): string;

    /**
     * @param array<int, FeedProductDTO> $products
     *
     * @return array{products: array<int, array{id:int, name:string, in_stock:bool}>}|string
     */
    public function render(array $products): array|string;
}
