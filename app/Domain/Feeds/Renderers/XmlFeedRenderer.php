<?php

namespace App\Domain\Feeds\Renderers;

use App\Domain\Feeds\Contracts\FeedRenderer;
use App\Domain\Feeds\DTOs\FeedProductDTO;
use DOMDocument;

final class XmlFeedRenderer implements FeedRenderer
{
    public function format(): string
    {
        return 'xml';
    }

    /**
     * @param array<int, FeedProductDTO> $products
     */
    public function render(array $products): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $productsNode = $document->createElement('products');
        $document->appendChild($productsNode);

        foreach ($products as $product) {
            $productNode = $document->createElement('product');
            $productsNode->appendChild($productNode);

            $productNode->appendChild($document->createElement('id', (string) $product->id));
            $productNode->appendChild($document->createElement('name', $product->name));
            $productNode->appendChild($document->createElement('in_stock', $product->inStock ? 'true' : 'false'));
        }

        return (string) $document->saveXML();
    }
}
