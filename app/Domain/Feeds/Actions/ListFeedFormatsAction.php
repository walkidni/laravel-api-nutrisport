<?php

namespace App\Domain\Feeds\Actions;

use App\Domain\Feeds\Support\FeedFormatRegistry;

final class ListFeedFormatsAction
{
    public function __construct(
        private readonly FeedFormatRegistry $feedFormatRegistry,
    ) {
    }

    /**
     * @return array{formats: array<int, array{format:string, url:string}>}
     */
    public function __invoke(): array
    {
        return [
            'formats' => collect($this->feedFormatRegistry->formats())
                ->map(fn (string $format): array => [
                    'format' => $format,
                    'url' => route('feeds.show', ['format' => $format]),
                ])
                ->all(),
        ];
    }
}
