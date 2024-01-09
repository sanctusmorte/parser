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

        $needTitles = [];
        $dataTitles = $this->prepareDataTitles($siteData);

        foreach ($dataTitles as $dataTitle) {
            if (str_word_count($dataTitle) <= 3) {
                $needTitles[] = $dataTitle;
            }
        }

        if ($needTitles < 10) {
            $types[] = 'Not Enough';
        }

        if (count($needTitles) > 0) {
            $foundTagsCount = $this->getTagsCountByHrefTitles($needTitles);
            $foundPornStarsCount = $this->getPornStarsCountByHrefTitles($needTitles);

            if ($foundTagsCount / count($needTitles) > 0.8) {
                $types[] = 'Tags List Page';
            }

            if ($foundPornStarsCount >= 10) {
                $types[] = 'Pornstars Page';
            }
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
            $items = HelperService::divideTextBySeparators($dataTitle);
            foreach ($items as $item) {
                if (!isset($needTitles[$item])) {
                    $needTitles[$item] = $item;
                }
            }
        }

        return $needTitles;
    }

    private function getDataTitles(LinkData $linkData)
    {
        if (is_null($linkData->href_titles) and is_null($linkData->img_alts)) {
            throw new \Exception('Href titles and img alts are null both');
        }

        $dataTitles = [];
        $hrefTitles = json_decode($linkData->href_titles, 1);
        $imgAlts = json_decode($linkData->img_alts, 1);

        foreach ($hrefTitles as $hrefTitle) {
            if (!isset($dataTitles[$hrefTitle])) {
                $dataTitles[$hrefTitle] = $hrefTitle;
            }
        }

        foreach ($imgAlts as $imgAlt) {
            if (!isset($dataTitles[$imgAlt])) {
                $dataTitles[$imgAlt] = $imgAlt;
            }
        }

        return $dataTitles;
    }

    private function getDataTitlesFromLinks(array $links)
    {
        $data = [];

        foreach ($links as $link) {
            $data[] =  $link['title'];
        }

        return $data;
    }

    private function removeBanWords($needTitles)
    {
        $data = [];
        $banned = [];

        foreach ($needTitles as $needTitle) {
            foreach (['facebook', 'Twitter', 'Google+', 'Google', 'Favorites', 'Pinterest', 'Tube'] as $item) {
                if (str_contains(strtolower($needTitle), strtolower($item))) {
                    if (!isset($banned[$needTitle])) {
                        $banned[$needTitle] = $needTitle;
                    }
                }
            }
        }

        foreach ($needTitles as $needTitle) {
            if (!in_array($needTitle, $banned)) {
                $data[$needTitle] = $needTitle;
            }
        }

        return array_values($data);
    }

    private function removeDomains($needTitles)
    {
        $data = [];
        $banned = [];

        foreach ($needTitles as $needTitle) {
            foreach (['.com', '.co', '.su', '.cc', '.pro'] as $item) {
                if (str_contains($needTitle, $item)) {
                    if (!isset($banned[$needTitle])) {
                        $banned[$needTitle] = $needTitle;
                    }
                }
            }
        }

        foreach ($needTitles as $needTitle) {
            if (!in_array($needTitle, $banned)) {
                $data[$needTitle] = $needTitle;
            }
        }

        return array_values($data);
    }

    /**
     * @throws \Exception
     */
    public function isSiteAdult(LinkData $linkData, array $links): bool
    {
        $needTitles = [];
        $dataTitles = $this->getDataTitles($linkData);


        if (empty($dataTitles) or count($dataTitles) < 20) {
            //dd('1331');
            $dataTitles = $this->getDataTitlesFromLinks($links);
        }

        foreach ($dataTitles as $dataTitle) {
            $items = HelperService::divideTextBySeparators($dataTitle);
            if (count($items) > 0) {
                foreach ($items as $item) {
                    $itemName = strtolower($item);
                    if (strlen($itemName) < 2) {
                        continue;
                    }
                    if (!isset($needTitles[$itemName])) {
                        $needTitles[$itemName] = $itemName;
                    }
                }
            }
            if (!isset($needTitles[$dataTitle])) {
                $needTitles[$dataTitle] = $dataTitle;
            }
        }

        $needTitles = array_values($needTitles);
        $needTitles = $this->removeDomains($needTitles);
        $needTitles = $this->removeBanWords($needTitles);

        if (count($needTitles) === 0) {
            return false;
        }

        $foundTagsCount = $this->getTagsCountByHrefTitles($needTitles);
        $foundPornStarsCount = $this->getPornStarsCountByHrefTitles($needTitles);

        if ($foundTagsCount < 3 and $foundPornStarsCount < 3) {
            $needTitles = $this->getNeedTitlesByLinks($links);
            $foundTagsCount = $this->getTagsCountByHrefTitles($needTitles);
            $foundPornStarsCount = $this->getPornStarsCountByHrefTitles($needTitles);
        }

        if ($foundTagsCount/count($needTitles) >= 0.3 or $foundPornStarsCount/count($needTitles) >= 0.3) {
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

    public function getTagsCountByHrefTitles(array $hrefTitles): int
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
