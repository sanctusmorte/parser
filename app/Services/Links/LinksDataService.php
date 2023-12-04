<?php

namespace App\Services\Links;

use App\Models\Link;

class LinksDataService
{
    public function getLinksToParse(): array
    {
        return Link::where(['status' => 0, 'level' => 1])->limit(30)->pluck('id')->toArray();
    }
}
