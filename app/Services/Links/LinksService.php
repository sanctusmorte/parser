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

    public function getThumbsTypesByLinks(array $links)
    {
        $pornStarsCount = 0;
        $types = [];
        $filteredTitles = [];
        $pornStarTitles = array_map(function ($item) {
            return trim($item['title']);
        }, $links);
        $titles = array_map(function ($item) {
            return explode(' ', $item['title']);
        }, $links);

        foreach ($titles as $titleGroup) {
            foreach ($titleGroup as $item) {
                if (strlen($item) >= 4) {
                    if (!in_array($item, $filteredTitles)) {
                        $filteredTitles[] = $item;
                    }
                }
            }
        }

        $pornStarsNames = Pornstar::pluck('external_full_name')->toArray();
        foreach ($pornStarsNames as $pornStarsName) {
            if (str_contains(implode('|', $pornStarTitles), $pornStarsName)) {
                $pornStarsCount++;
            }
        }

        $tagsCount = Tag::whereIn('name', $filteredTitles)->get()->count();

        if ($tagsCount >= 20) {
            $types[] = 'Tags List Page';
        }

        if ($pornStarsCount >= 10) {
            $types[] = 'Pornstars List Page';
        }

        return $types;
    }
}
