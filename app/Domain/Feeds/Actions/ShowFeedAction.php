<?php

namespace App\Domain\Feeds\Actions;

use App\Domain\Feeds\Queries\ListFeedProductsQuery;
use App\Domain\Feeds\Support\FeedFormatRegistry;
use App\Domain\Shared\SiteContext\Site;

final class ShowFeedAction
{
    public function __construct(
        private readonly ListFeedProductsQuery $listFeedProductsQuery,
        private readonly FeedFormatRegistry $feedFormatRegistry,
    ) {
    }

    /**
     * @return array{products: array<int, array{id:int, name:string, in_stock:bool}>}|string
     */
    public function __invoke(Site $site, string $format): array|string
    {
        $products = ($this->listFeedProductsQuery)($site);
        $renderer = $this->feedFormatRegistry->renderer($format);

        return $renderer->render($products);
    }
}
