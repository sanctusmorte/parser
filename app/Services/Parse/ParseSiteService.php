<?php

namespace App\Services\Parse;

use App\Console\Commands\ParseLinksLevelOneCommand;
use App\Jobs\HandleSiteMasksJob;
use App\Jobs\ParseLinksJob;
use App\Jobs\UpdateMasksForAllSitesJob;
use App\Models\Link;
use App\Models\LinkData;
use App\Models\Site;
use App\Services\DOMService;
use App\Services\GuzzleService;
use App\Services\HelperService;
use App\Services\Links\LinksService;
use App\Services\Parse\Exceptions\ParseSiteBadResponseException;
use App\Services\Proxy\ProxyService;
use App\Services\Sites\Enum\SiteTypeEnum;
use App\Services\Sites\SitesService;
use App\Services\Thumbs\ThumbsService;
use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use PHPHtmlParser\Exceptions\CurlException;

class ParseSiteService
{
    const MODEL_NOT_FOUND_ERROR = '[ID:%s][PARSE_%s] Ошибка парсинга, сайт не найден в БД';
    const BAD_RESPONSE_ERROR = '[ID:%s][PARSE_%s] Ошибка парсинга - %s';
    const DEBUG_START = '[PARSE_%s] Начали парсить сайт [ID:%s]';
    const DEBUG_GOT_HTML = '[PARSE_%s] Успешно спарсили, разбираем HTML [ID:%s]';
    const DEBUG_READY_TO_SAVE = '[PARSE_%s] Разобрали HTML, вытащили данные, сохраняем [ID:%s]';

    const SITE = 'SITE';
    const LINK = 'LINK';
    const MIN_LINKS = 15;

    private GuzzleService $guzzleService;
    private DOMService $DOMService;
    private LinksService $linksService;
    private ProxyService $proxyService;
    private SitesService $sitesService;
    private ThumbsService $thumbsService;

    public function __construct(GuzzleService $guzzleService, DOMService $DOMService, LinksService $linksService, ProxyService $proxyService, SitesService $sitesService, ThumbsService $thumbsService)
    {
        $this->guzzleService = $guzzleService;
        $this->DOMService = $DOMService;
        $this->linksService = $linksService;
        $this->proxyService = $proxyService;
        $this->sitesService = $sitesService;
        $this->thumbsService = $thumbsService;
    }

    /**
     * @throws Exception
     */
    public function parseSite(int $siteId): void
    {
        try {
            $this->startParseSite($siteId);
        } catch (ModelNotFoundException) {
            Log::error(sprintf(self::MODEL_NOT_FOUND_ERROR, $siteId, self::SITE));
        } catch (HttpClientException|ParseSiteBadResponseException $e) {
            $trace = array_slice($e->getTrace(), 0, 3);;
            Log::error(sprintf(self::BAD_RESPONSE_ERROR, self::SITE, $siteId, $e->getMessage()), $trace);
        } catch (Exception $e) {
            $existSite = Site::find($siteId);
            if (!is_null($existSite)) {
                $existSite->status = 3;
                $existSite->save();
            }
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function parseLink(int $linkId, ?int $level = null): void
    {
        try {
            $this->startParseLink($linkId, $level);
        } catch (ModelNotFoundException) {
            Log::error(sprintf(self::MODEL_NOT_FOUND_ERROR, $linkId, self::LINK));
        } catch (HttpClientException|ParseSiteBadResponseException $e) {
            $trace = array_slice($e->getTrace(), 0, 3);;
            Log::error(sprintf(self::BAD_RESPONSE_ERROR, self::LINK, $linkId, $e->getMessage()), $trace);
        } catch (Exception $e) {
            $existLink = Link::find($linkId);

            if (!is_null($existLink)) {
                $existLink->status = 3;
                $existLink->save();
            }

            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HttpClientException
     * @throws \stringEncode\Exception
     */
    public function startParseSite(int $siteId): int
    {
        $existSite = Site::find($siteId);
        //$proxy = $this->proxyService->getAvailableProxy();

        if (is_null($existSite)) {
            throw new ModelNotFoundException();
        }

        Log::debug(sprintf(self::DEBUG_START, self::SITE, $siteId));

        try {
            $response = $this->guzzleService->getRequest($existSite->link_url);
        } catch (HttpClientException $e) {
            $existSite->status = 3;

            $existSite->save();
            throw new HttpClientException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$response->ok()) {
            $existSite->status = 3;
            $existSite->save();
            throw new ParseSiteBadResponseException($response->body(), $response->status());
        }

        Log::debug(sprintf(self::DEBUG_GOT_HTML, self::SITE, $siteId));

        $isRedirect = false;
        $html = $response->body();
        $headers = $response->headers();
        if (isset($headers['X-Guzzle-Redirect-History'])) {
            $isRedirect = true;
        }
        $response->close();

        $this->setDataToSite($html, $existSite, $isRedirect);


        HandleSiteMasksJob::dispatch($siteId);

        return 1;
    }

    public function startParseLink(int $linkId, ?int $level = null): int
    {
        $existLink = Link::find($linkId);

        if (is_null($existLink)) {
            throw new ModelNotFoundException();
        }

        Log::debug(sprintf('Начали парсить линк - %s', $linkId));

        try {
            $response = $this->guzzleService->getRequest($existLink->link_url);
        } catch (HttpClientException $e) {
            $existLink->status = 3;
            $existLink->save();
            throw new HttpClientException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$response->ok()) {
            $existLink->status = 3;
            $existLink->save();
            throw new ParseSiteBadResponseException($response->body(), $response->status());
        }

        Log::debug(sprintf(self::DEBUG_GOT_HTML, self::SITE, $linkId));

        $isRedirect = false;
        $html = $response->body();
        $headers = $response->headers();
        $domain = substr($existLink->link_url, 8);
        if (isset($headers['X-Guzzle-Redirect-History'])) {
            $redirectLink = $headers['X-Guzzle-Redirect-History'][0];
            if (!str_contains($redirectLink, $domain)) {
                $isRedirect = true;
            }

        }

        $response->close();

        $this->setDataToLink($html, $existLink, $isRedirect, $level);

        return 1;
    }

    private function getRandomLinks(array $links): array
    {
        $data = [];
        $ids = array_rand($links, 30);

        foreach ($ids as $id) {
            $data[] = $links[$id];
        }

        return $data;
    }

    private function setDataToLink(string $html, Link $link, int $isRedirect, ?int $level = null)
    {
        $domain = $this->getDomainFromLinkUrl($link->link_url);
        $linkData = LinkData::where('parent_link_id', $link->id)->firstOrCreate();

        $thumbsTypes = [];

        if (empty($thumbsTypes)) {
            $thumbsTypes = ['test'];
        }

        try {
            $linkData = $this->setLinkData($linkData, $html, 'link', $link->id, $isRedirect, $domain, $level, thumbsTypes: $thumbsTypes);
            $linkData->save();
        } catch (Exception $e) {
            Log::error('wtf не могу данные сохранить', [$e]);
        }

        $link->status = 1;
        $link->link_data_id = $linkData->id;

        $link->save();
    }

    private function getDomainFromLinkUrl(string $linkUrl): string
    {
        return strpos($linkUrl, 'http://') === false ? substr($linkUrl, 8) : substr($linkUrl, 7);
    }

    private function setDataToSite(string $html, Site $site, int $isRedirect)
    {
        try {
            $domain = $this->getDomainFromLinkUrl($site->link_url);
            $links = $this->DOMService->getAllLinks($html, $domain);
            $queryLinks = $this->DOMService->getAllQueryLinks($html);
            $thumbsTypes = [];

            if (count($queryLinks) > 3) {
                $thumbsTypes[] = 'Search List Page';
            }
        } catch (Exception $e) {
            throw new Exception();
        }

        $linkData = LinkData::where('parent_site_id',$site->id)->firstOrCreate();

        $linkData = $this->setLinkData($linkData, $html, 'site', $site->id, $isRedirect, $site->link_url, null, $thumbsTypes);

        $site->status = $this->detectSiteStatusByLinksAndMatchedData($links, $linkData);
        $site->site_type = $this->detectSiteTypeByLinks($links, $site);
        $site->link_data_id = $linkData->id;
        $site->save();

        $linkData->save();

        if (count($links) >= 15 && $site->status === 1) {
            Link::upsert($this->getLinksInsertData($links, $site->id), ['link_url'], ['link_url', 'parent_id', 'path_url']);
        }

        $linkData->refresh();

        if ($site->status === 1) {
            $this->sitesService->parseAndSaveTextTemplateForSiteById($site->id);
        }
    }

    private function detectSiteTypeByLinks($links, Site $site): int
    {
        if ($site->status !== 1) {
            return SiteTypeEnum::NONE;
        }

        $count = 0;

        if (count($links) < 15) {
            return SiteTypeEnum::NONE;
        }

        $matches = ['video', 'videos', 'play', 'watch', 'film', 'films'];
        foreach ($links as $link) {
            $url = strtolower($link['path_url']);
            if (strlen($url) < 5) {
                continue;
            }
            $items = explode('/', $url);
            foreach ($items as $item) {
                if (strlen($item) < 3) {
                    continue;
                }
                if (in_array($item, $matches)) {
                    $count++;
                }
            }
        }

        if ($count/count($links) > 0.5) {
            return SiteTypeEnum::VIDEOS;
        }

        $needTitles = [];

        foreach ($links as $link) {
            if (str_word_count($link['title']) <= 4) {
                $words = HelperService::divideTextBySeparators($link['title']);
                if (is_null($words)) {
                    dd($words, $link);
                }
                foreach ($words as $word) {
                    if (!isset($needTitles[$word])) {
                        $needTitles[$word] = $word;
                    }
                }
            }
        }

        if (count($needTitles)/count($links) < 0.7) {
            $foundTagsCount = $this->thumbsService->getTagsCountByHrefTitles($needTitles);
            dd($foundTagsCount, $links, $needTitles);
        }

        dd($needLinks, $links);

        return SiteTypeEnum::NONE;
    }

    private function detectSiteStatusByLinksAndMatchedData(array $links, LinkData $linkData)
    {
        if (count($links) < 5) {
            return 2;
        }

        $isSiteAdult = $this->thumbsService->isSiteAdult($linkData, $links);

        if ($isSiteAdult) {
            return 1;
        }

        return 2;
    }

    private function getLinksInsertData(array $links, int $parentId): array
    {
        $linksInsertData = [];

        foreach ($links as $link) {
            $linksInsertData[] = [
                'parent_id' => $parentId,
                'link_url' => $link['href'],
                'path_url' => $link['path_url'],
                'level' => 1,
                'status' => 0,
            ];
            if (count($linksInsertData) >= 5) {
                return $linksInsertData;
            }
        }

        return $linksInsertData;
    }

    private function setLinkData(LinkData $linkData, string $html, string $parentType, int $parentId, int $isRedirect, string $linkUrl, ?int $level = null, ?array $thumbsTypes = null)
    {
        $linkData->type = '';
        $linkData->meta_title = $this->DOMService->getMetaTitle($html);
        $linkData->meta_description = $this->DOMService->getMetaDescription($html);
        $linkData->meta_keywords = $this->DOMService->getMetaKeywords($html);
        $linkData->h_tags = json_encode($this->DOMService->getHTags($html));
        $linkData->img_alts = json_encode($this->DOMService->getImgAlts($html));
        $linkData->is_redirect = $isRedirect;
        $linkData->href_titles = json_encode($this->DOMService->getHrefTitles($html, $linkUrl));
        $linkData->content_thumb = json_encode($thumbsTypes);

        if (!is_null($level)) {
            $linkData->level = $level;
        }

        if ($parentType === 'site') {
            $linkData->parent_site_id = $parentId;
        }

        if ($parentType === 'link') {
            $linkData->parent_link_id = $parentId;
        }

        return $linkData;
    }

    private function checkLinks(array $links)
    {
        $data = [
            'not_redirect' => [],
            'redirect' => [],
            'errors' => [],
        ];

        foreach ($links as $link) {

            $isRedirect = false;
            try {
                $response = $this->guzzleService->getRequest($link);
                $headers = $response->headers();
                $response->close();
                if (isset($headers['X-Guzzle-Redirect-History'])) {
                    $isRedirect = true;
                }
                $key = $isRedirect ? 'redirect' : 'not_redirect';
            } catch (\Exception $e) {
                $key = 'errors';
            }

            $data[$key][] = $link;

            sleep(1);
        }

        return $data;
    }
}
