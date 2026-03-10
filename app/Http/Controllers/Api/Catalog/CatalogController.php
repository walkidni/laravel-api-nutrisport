<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Catalog\Queries\FindProductForSite;
use App\Domain\Catalog\Queries\ListProducts;
use App\Domain\Shared\SiteContext\CurrentSiteResolver;
use App\Domain\Shared\SiteContext\Site;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Catalog\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CatalogController extends Controller
{
    public function index(Request $request, ListProducts $listProducts): AnonymousResourceCollection
    {
        return ProductResource::collection($listProducts($this->currentSite($request)));
    }

    public function show(
        Request $request,
        int $product,
        FindProductForSite $findProductForSite,
    ): ProductResource {
        $resolvedProduct = $findProductForSite($this->currentSite($request), $product);

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
