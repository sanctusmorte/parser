<?php

namespace App\Services\Sites;

use App\Models\Link;
use App\Models\Site;

class SitesDataService
{
    public function getLinksToParse(): array
    {
        return Site::where(['status' => 0])->where('id', '<=', 100)->limit(1)->pluck('id')->toArray();
    }
}
