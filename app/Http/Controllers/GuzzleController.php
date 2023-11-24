<?php

namespace App\Http\Controllers;

use App\Services\ParseSiteService;

class GuzzleController extends Controller
{
    public function test(ParseSiteService $service)
    {
        $service->firstParse(7);
    }
}
