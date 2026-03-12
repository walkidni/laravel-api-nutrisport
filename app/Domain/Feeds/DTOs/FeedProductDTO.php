<?php

namespace App\Domain\Feeds\DTOs;

final readonly class FeedProductDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $inStock,
    ) {
    }

    /**
     * @return array{id:int, name:string, in_stock:bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'in_stock' => $this->inStock,
        ];
    }
}
