<?php

namespace App\Http\Controllers\Api\Feeds;

use App\Domain\Feeds\Actions\ListFeedFormatsAction;
use App\Domain\Feeds\Actions\ShowFeedAction;
use App\Domain\Feeds\Exceptions\UnsupportedFeedFormatException;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FeedController extends Controller
{
    public function __construct(
        private readonly CurrentSiteContextService $currentSiteContextService,
    ) {
    }

    public function index(ListFeedFormatsAction $listFeedFormatsAction): JsonResponse
    {
        return response()->json($listFeedFormatsAction());
    }

    public function show(
        Request $request,
        string $format,
        ShowFeedAction $showFeedAction,
    ): Response {
        try {
            $payload = $showFeedAction($this->currentSiteContextService->get($request), $format);
        } catch (UnsupportedFeedFormatException $exception) {
            throw new NotFoundHttpException(previous: $exception);
        }

        return response($payload->body, 200, [
            'Content-Type' => $payload->contentType,
        ]);
    }
}
