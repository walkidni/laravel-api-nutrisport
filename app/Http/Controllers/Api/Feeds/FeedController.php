<?php

namespace App\Http\Controllers\Api\Feeds;

use App\Domain\Feeds\Actions\ListFeedFormatsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class FeedController extends Controller
{
    public function index(ListFeedFormatsAction $listFeedFormatsAction): JsonResponse
    {
        return response()->json($listFeedFormatsAction());
    }
}
