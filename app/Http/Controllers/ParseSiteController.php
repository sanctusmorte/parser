<?php

namespace App\Http\Controllers;

use App\Jobs\FirstParseSiteJob;
use App\Services\Parse\ParseSiteService;

class ParseSiteController extends Controller
{
    public function parse($siteId)
    {
        FirstParseSiteJob::dispatch($siteId);

        return redirect()->back()->with(['message' => "Сайт с id ".$siteId." успешно добавлен в очередь на парсинг!", 'alert-type' => 'success']);
    }

    public function parseDebug(int $siteId, ParseSiteService $service)
    {
        $service->parse($siteId);
    }
}
