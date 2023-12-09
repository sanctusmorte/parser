<?php

namespace App\Services\Masks;

use App\Models\Link;
use App\Models\LinksMask;
use App\Models\Pornstar;
use App\Models\Site;
use App\Models\SitesLinksMask;
use stringEncode\Exception;

class MasksService
{
    /**
     * @throws Exception
     */
    public function handleMasksForSite(int $siteId)
    {
        $existSite = Site::find($siteId);

        if (empty($existSite)) {
            throw new Exception();
        }

        $grouped = [];
        $linksGrouped = [];


        $linksMasks = LinksMask::where('status', 1)->get();
        $links = $existSite->links()->get();

        foreach ($linksMasks as $linksMask) {
            foreach ($links as $link) {
                if (str_contains(strtolower($link->link_url), strtolower($linksMask->name))) {
                    $grouped[$linksMask->id] = isset($grouped[$linksMask->id]) ? $grouped[$linksMask->id] + 1 : 1;
                    if (!isset($linksGrouped[$link->link_url])) {
                        $linksGrouped[$link->link_url] = [$linksMask->id];
                    } else {
                        $linksGrouped[$link->link_url][] = $linksMask->id;
                    }

                }
            }
        }

        $insertData = [];
        $linksInsertData = [];

        foreach ($grouped as $maskId => $maskCount) {
            $insertData[] = [
                'site_id' => $siteId,
                'mask_id' => $maskId,
                'links_count' => $maskCount,
            ];
        }

        foreach ($linksGrouped as $linkUrl => $linkMaskIds) {
            $linksInsertData[] = [
                'parent_id' => $siteId,
                'link_url' => $linkUrl,
                'mask_ids' => json_encode($linkMaskIds),
            ];
        }

        SitesLinksMask::upsert($insertData, ['site_id', 'mask_id'], ['links_count']);
        Link::upsert($linksInsertData, ['link_url'], ['mask_ids']);
    }
}
