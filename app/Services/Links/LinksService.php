<?php

namespace App\Services\Links;

use App\Jobs\ParseLinksJob;
use App\Models\Link;
use App\Models\Pornstar;
use App\Models\Tag;

class LinksService
{
    public function parseLinksForSite(int $siteId)
    {
        $links = Link::where(['parent_id' => $siteId, 'status' => 0])->pluck('id')->all();

        foreach ($links as $link) {
            ParseLinksJob::dispatch($link);
        }
    }
}
