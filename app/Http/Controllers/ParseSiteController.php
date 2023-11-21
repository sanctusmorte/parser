<?php

namespace App\Http\Controllers;

use App\Jobs\FirstParseSiteJob;

class ParseSiteController extends Controller
{
    public function first($siteId)
    {
        FirstParseSiteJob::dispatch($siteId);

        return redirect()->back()->with(['message' => "Сайт с id ".$siteId." успешно добавлен в очередь на парсинг!", 'alert-type' => 'success']);
    }
}
