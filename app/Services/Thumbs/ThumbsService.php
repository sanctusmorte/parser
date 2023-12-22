<?php

namespace App\Services\Thumbs;

use App\Models\Link;
use App\Models\LinkData;
use App\Models\Site;
use App\Models\Tag;
use App\Services\Links\LinksService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stringEncode\Exception;

class ThumbsService
{
    const INFO_MSG_NOT_ENOUGH_PARSED_LINKS = 'Не парсим thumbs-type у сайта (%s), потому что успешно спаршенных ссылок у этого сайта < 5';
    private LinksService $linksService;

    public function __construct(LinksService $linksService)
    {
        $this->linksService = $linksService;
    }

    /**
     * @throws Exception
     */
    public function parseThumbsTypeForSite(int $siteId)
    {
        $site = Site::find($siteId);
        $siteData = LinkData::where('parent_site_id', $siteId)->first();

        if (is_null($siteData) or is_null($site)) {
            throw new ModelNotFoundException();
        }

        if (is_null($siteData->href_titles) and is_null($siteData->img_alts)) {
            throw new \Exception('Href titles and img alts are null both, cant parse thumbs type, site id - ' . $siteId);
        }

        $types = json_decode($siteData->content_thumb, 1) ?? [];

        $hrefTitles = json_decode($siteData->href_titles, 1);
        $imgAlts = json_decode($siteData->img_alts, 1);


        dd($hrefTitles, $imgAlts);

        $needTitles = $hrefTitles;

        if (count($hrefTitles) < 10 and count($imgAlts) < 10) {
            throw new \Exception('Count of href titles and count of img alt < 10, cant parse thumbs type, site id - ' . $siteId);
        }

        if (count($hrefTitles) < 10) {
            $needTitles = $imgAlts;
        }

        $foundTagsCount = $this->getTagsCountByHrefTitles($needTitles);
        $foundPornStarsCount = $this->getPornStarsCountByHrefTitles($needTitles);

        if ($foundTagsCount / count($needTitles) > 0.5) {
            $types[] = 'Tags List Page';
        }

        if ($foundPornStarsCount >= 10) {
            $types[] = 'Pornstars Page';
        }

        $siteData->content_thumb = json_encode($types);
        $site->is_thubms_type_parsed = 1;
        $site->save();
        $siteData->save();
    }

    private function getType()
    {

    }

    private function getTagsCountByHrefTitles(array $hrefTitles): int
    {
        $tags = DB::table('tags');
        foreach ($hrefTitles as $hrefTitle) {
            $tags->orWhere('name', $hrefTitle);
        }

        return $tags->count();
    }

    private function getPornStarsCountByHrefTitles(array $hrefTitles): int
    {
        $pornStars = DB::table('pornstars');
        foreach ($hrefTitles as $hrefTitle) {
            $pornStars->orWhere('external_full_name', $hrefTitle);
        }

        return $pornStars->count();
    }
}
