<?php

namespace App\Http\Middleware;

use App\Domain\Shared\SiteContext\CurrentSiteResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResolveCurrentSite
{
    public function __construct(
        private readonly CurrentSiteResolver $currentSiteResolver,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $site = $this->currentSiteResolver->resolve($request);

        if ($site === null) {
            throw new NotFoundHttpException();
        }

        $request->attributes->set(CurrentSiteResolver::REQUEST_ATTRIBUTE, $site);

        return $next($request);
    }
}
