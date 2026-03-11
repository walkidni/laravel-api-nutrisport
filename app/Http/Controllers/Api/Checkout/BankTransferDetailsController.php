<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use App\Domain\Shared\SiteContext\Site;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Checkout\BankTransferDetailsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankTransferDetailsController extends Controller
{
    public function __construct(
        private readonly CurrentSiteContextService $currentSiteContextService,
    ) {
    }

    public function show(Request $request): JsonResource
    {
        $site = $this->currentSiteContextService->get($request);
        $siteCode = (string) $site->getAttribute(Site::CODE);

        return BankTransferDetailsResource::make(
            (array) config("payments.bank_transfer_details.{$siteCode}", []),
        );
    }
}
