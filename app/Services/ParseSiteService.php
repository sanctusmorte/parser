<?php

namespace App\Services;

use App\Models\Site;
use App\Models\SiteDetail;

class ParseSiteService
{
    public function firstParse(int $siteId)
    {
        $existSite = Site::find($siteId);

        if (!is_null($existSite)) {
            $existSite->is_first_parsed = 1;
            $existSite->save();

            $newDetail = new SiteDetail();
            $newDetail->site_id = $siteId;
            $newDetail->type = 'Test type (CJ, Tube, CJ/Tube)';
            $newDetail->is_suitable = 0;
            $newDetail->save();
        }
    }
}
