<?php

namespace App\Services\Sites;

use App\Jobs\HandleSiteMasksJob;
use App\Jobs\ParseLinksJob;
use App\Models\Link;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

class SitesService
{
    public function updateMasksForAllSites()
    {
       $sites = Site::where('status', 1)->pluck('id')->toArray();

        foreach ($sites as $siteId) {
            HandleSiteMasksJob::dispatch($siteId);
        }
    }

    public function deleteMaskIdsForAllLinks(int $maskId)
    {
        $updateData = [];
        $links = Link::whereJsonContains('mask_ids', $maskId)->get();

        foreach ($links as $link) {
            $updateData[] = [
                'id' => $link->id,
                'mask_ids' => null
            ];
        }

        Link::upsert($updateData, ['id'], ['mask_ids']);

        $this->updateMasksForAllSites();
    }
}
