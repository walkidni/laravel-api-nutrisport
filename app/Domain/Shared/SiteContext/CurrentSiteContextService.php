<?php

namespace App\Domain\Shared\SiteContext;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CurrentSiteContextService
{
    public function get(Request $request): Site
    {
        $site = $request->attributes->get(CurrentSiteResolver::REQUEST_ATTRIBUTE);

        if ($site instanceof Site) {
            return $site;
        }

        throw new NotFoundHttpException();
    }
}
