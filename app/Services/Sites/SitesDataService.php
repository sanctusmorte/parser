<?php

namespace App\Services\Sites;

use App\Models\Link;
use App\Models\Site;

class SitesDataService
{
    public function getLinksToParse(): array
    {
        return Site::where(['status' => 0])->where('id', '<=', 200)->limit(10)->pluck('id')->toArray();
    }

    public function getSitesWithoutThumbsType(): array
    {
        return Site::where(['status' => 1, 'is_thubms_type_parsed' => 0])->limit(5)->pluck('id')->toArray();
    }
}
