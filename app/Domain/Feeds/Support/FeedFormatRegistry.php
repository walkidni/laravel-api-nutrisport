<?php

namespace App\Domain\Feeds\Support;

use App\Domain\Feeds\Contracts\FeedRenderer;
use App\Domain\Feeds\Exceptions\UnsupportedFeedFormatException;
use App\Domain\Feeds\Renderers\JsonFeedRenderer;
use App\Domain\Feeds\Renderers\XmlFeedRenderer;

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

    public function renderer(string $format): FeedRenderer
    {
        return match ($format) {
            'json' => app(JsonFeedRenderer::class),
            'xml' => app(XmlFeedRenderer::class),
            default => throw new UnsupportedFeedFormatException("Unsupported feed format [{$format}]."),
        };
    }
}
