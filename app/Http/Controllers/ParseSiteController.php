<?php

namespace App\Http\Controllers;

use App\Jobs\ParseSiteJob;
use App\Jobs\ParseLinksJob;
use App\Models\Site;
use App\Services\Links\LinksService;
use App\Services\Parse\ParseSiteService;
use App\Services\Thumbs\ThumbsService;
use Exception;

class ParseSiteController extends Controller
{
    public function parse($siteId)
    {
        ParseSiteJob::dispatch($siteId);

        return redirect()->back()->with(['message' => "Сайт с id ".$siteId." успешно добавлен в очередь на парсинг!", 'alert-type' => 'success']);
    }

    public function parseDebug(int $siteId, ParseSiteService $service)
    {
        $service->parseSite($siteId);
    }

    public function parseLinkDebug(int $linkId, ParseSiteService $service)
    {
      // dispatch(new ParseLinksJob($linkId));
      //  ParseLinkJob::dispatch($linkId);
        $service->parseLink($linkId);
    }

    public function parseThumbTypeForSite(int $siteId, ThumbsService $service)
    {
        $service->parseThumbsTypeForSite($siteId);
    }

}
