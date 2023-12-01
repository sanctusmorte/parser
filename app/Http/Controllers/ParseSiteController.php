<?php

namespace App\Http\Controllers;

use App\Jobs\ParseSiteJob;
use App\Jobs\ParseLinksJob;
use App\Models\Site;
use App\Services\Parse\ParseSiteService;
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
        dd(Site::where(['status' => 0])->where('id', '<=', 100)->limit(1)->pluck('id')->toArray());
      // dispatch(new ParseLinksJob($linkId));
      //  ParseLinkJob::dispatch($linkId);
        //$service->parseLink($linkId);
    }
}
