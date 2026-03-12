<?php

namespace App\Domain\Feeds\Contracts;

use App\Domain\Feeds\DTOs\FeedProductDTO;

interface FeedRenderer
{
    public function format(): string;

    public function contentType(): string;

    /**
     * @param array<int, FeedProductDTO> $products
     */
    public function render(array $products): string;
}
