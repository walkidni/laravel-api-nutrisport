<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Catalog\Queries\FindProductForSiteQuery;
use App\Domain\Catalog\Queries\ListProductsQuery;
use App\Domain\Shared\SiteContext\CurrentSiteResolver;
use App\Domain\Shared\SiteContext\Site;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Catalog\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CatalogController extends Controller
{
    public function index(Request $request, ListProductsQuery $listProductsQuery): AnonymousResourceCollection
    {
        return ProductResource::collection($listProductsQuery($this->currentSite($request)));
    }

    public function show(
        Request $request,
        int $product,
        FindProductForSiteQuery $findProductForSiteQuery,
    ): ProductResource {
        $resolvedProduct = $findProductForSiteQuery($this->currentSite($request), $product);

        if ($resolvedProduct === null) {
            throw new NotFoundHttpException();
        }

        return ProductResource::make($resolvedProduct);
    }

    private function currentSite(Request $request): Site
    {
        $site = $request->attributes->get(CurrentSiteResolver::REQUEST_ATTRIBUTE);

        if ($site instanceof Site) {
            return $site;
        }

        throw new NotFoundHttpException();
    }
}
