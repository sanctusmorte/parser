<?php

namespace App\Services\Links;

use App\Models\Link;

class LinksDataService
{
    public function getLinksToParse(): array
    {
        return Link::where('status', 0)->limit(30)->pluck('id')->toArray();
    }
}
