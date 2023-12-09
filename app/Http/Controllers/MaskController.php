<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Services\Masks\MasksService;
use App\Services\Sites\SitesService;
use Illuminate\Support\Facades\DB;

class MaskController extends Controller
{
    public function index($siteId, MasksService $service, SitesService $sitesService)
    {
        //$service->handleMasksForSite(1);
        $sitesService->updateMasksForAllSites(3);
    }
}
