<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Catalog\Queries\FindProductForSiteQuery;
use App\Domain\Catalog\Queries\ListProductsQuery;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Catalog\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CatalogController extends Controller
{
    public function __construct(
        private readonly CurrentSiteContextService $currentSiteContextService,
    ) {
    }

    public function index(Request $request, ListProductsQuery $listProductsQuery): AnonymousResourceCollection
    {
        return ProductResource::collection($listProductsQuery($this->currentSiteContextService->get($request)));
    }

    public function show(
        Request $request,
        int $product,
        FindProductForSiteQuery $findProductForSiteQuery,
    ): ProductResource {
        $resolvedProduct = $findProductForSiteQuery($this->currentSiteContextService->get($request), $product);

        if ($resolvedProduct === null) {
            throw new NotFoundHttpException();
        }

        return ProductResource::make($resolvedProduct);
    }
}
