<?php

namespace App\Services\Thumbs;

use App\Models\Link;
use App\Models\LinkData;
use App\Models\Site;
use App\Models\Tag;
use App\Services\HelperService;
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
     * @throws \Exception
     */
    public function parseThumbsTypeForSite(int $siteId): void
    {
        $site = Site::find($siteId);
        $siteData = LinkData::where('parent_site_id', $siteId)->first();

        if (is_null($siteData) or is_null($site)) {
            throw new ModelNotFoundException();
        }

        $types = [];

        $needTitles = $this->prepareDataTitles($siteData);

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

    private function prepareDataTitles(LinkData $linkData): array
    {
        $dataTitles = $this->getDataTitles($linkData);
        $needTitles = [];

        foreach ($dataTitles as $dataTitle) {
            foreach ([',', '+', 'and', ' '] as $separator) {
                if (str_contains($dataTitle, $separator)) {
                    $explodeItems = explode($separator, $dataTitle);
                    foreach ($explodeItems as $explodeItem) {
                        if (strlen($explodeItem) >= 2) {
                            $explodeItem = str_replace(':', '', $explodeItem);
                            $explodeItem = str_replace('+', '', $explodeItem);
                            $explodeItem = trim($explodeItem);
                            if (!isset($needTitles[$explodeItem])) {
                                $needTitles[$explodeItem] = $explodeItem;
                            }
                        }
                    }
                }
            }
            if (!isset($needTitles[$dataTitle])) {
                $needTitles[$dataTitle] = $dataTitle;
            }
        }

        return $needTitles;
    }

    private function getDataTitles(LinkData $linkData)
    {
        if (is_null($linkData->href_titles) and is_null($linkData->img_alts)) {
            throw new \Exception('Href titles and img alts are null both');
        }

        $hrefTitles = json_decode($linkData->href_titles, 1);
        $imgAlts = json_decode($linkData->img_alts, 1);

        $dataTitles = $hrefTitles;

        if (count($hrefTitles) < 10 and count($imgAlts) < 10) {
            throw new \Exception('Count of href titles and count of img alt < 10');
        }

        if (count($hrefTitles) < 10) {
            $dataTitles = $imgAlts;
        }

        return $dataTitles;
    }

    public function isSiteAdult(LinkData $linkData, array $links): bool
    {
        $needTitles = $this->prepareDataTitles($linkData);

        $foundTagsCount = $this->getTagsCountByHrefTitles($needTitles);
        $foundPornStarsCount = $this->getPornStarsCountByHrefTitles($needTitles);

        if ($foundTagsCount < 3 and $foundPornStarsCount < 3) {
            $needTitles = $this->getNeedTitlesByLinks($links);
            $foundTagsCount = $this->getTagsCountByHrefTitles($needTitles);
            $foundPornStarsCount = $this->getPornStarsCountByHrefTitles($needTitles);
        }

        if ($foundTagsCount/count($needTitles) >= 0.4 or $foundPornStarsCount/count($needTitles) >= 0.4) {
            return true;
        }

        return false;
    }

    private function getNeedTitlesByLinks(array $links)
    {
        $data = [];

        foreach ($links as $link) {
            $title = strtolower(trim($link['title']));
            $paths = HelperService::divideTextBySeparators($link['path_url']);
            $paths[] = $title;

            foreach ($paths as $path) {
                if (!isset($data[$path])) {
                    $data[$path] = $path;
                }
            }
        }

        return $data;
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
