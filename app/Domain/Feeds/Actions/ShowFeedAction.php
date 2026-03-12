<?php

namespace App\Domain\Feeds\Actions;

use App\Domain\Feeds\DTOs\RenderedFeedDTO;
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

    public function __invoke(Site $site, string $format): RenderedFeedDTO
    {
        $products = ($this->listFeedProductsQuery)($site);
        $renderer = $this->feedFormatRegistry->renderer($format);

        return new RenderedFeedDTO(
            body: $renderer->render($products),
            contentType: $renderer->contentType(),
        );
    }
}
