<?php

namespace App\Services\Parse;

use App\Models\Link;
use App\Models\LinkData;
use App\Models\Site;
use App\Services\DOMService;
use App\Services\GuzzleService;
use App\Services\Parse\Exceptions\ParseSiteBadResponseException;
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

    private GuzzleService $guzzleService;
    private DOMService $DOMService;

    public function __construct(GuzzleService $guzzleService, DOMService $DOMService)
    {
        $this->guzzleService = $guzzleService;
        $this->DOMService = $DOMService;
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
            throw new Exception();
        }
    }

    public function parseLink(int $linkId): void
    {
        try {
            $this->startParseLink($linkId);
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

            throw new Exception();
        }
    }

    /**
     * @throws HttpClientException
     */
    public function startParseSite(int $siteId): int
    {
        $existSite = Site::find($siteId);

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

        $html = $response->body();
        $response->close();

        $this->setDataToSite($html, $existSite);

        return 1;
    }

    public function startParseLink(int $linkId): int
    {
        $existLink = Link::find($linkId);

        if (is_null($existLink)) {
            throw new ModelNotFoundException();
        }

        Log::debug(sprintf(self::DEBUG_START, self::SITE, $linkId));

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

        $html = $response->body();
        $response->close();

        $this->setDataToLink($html, $existLink);

        return 1;
    }

    private function setDataToLink(string $html, Link $link)
    {
        $linkData = LinkData::where('parent_link_id', $link->id)->first();

        if (is_null($linkData)) {
            $linkData = new LinkData();
        }

        $this->setLinkData($linkData, $html, 'link', $link->id);

        $link->status = 1;
        $link->link_data_id = $linkData->id;
        $link->save();
    }

    private function setDataToSite(string $html, Site $site)
    {
        $domain = strpos($site->link_url, 'http://') === false ? substr($site->link_url, 8) : ubstr($site->link_url, 7);
        $links = $this->DOMService->getAllLinks($html, $domain);

        $linkData = LinkData::where('parent_site_id', $site->id)->first();

        if (is_null($linkData)) {
            $linkData = new LinkData();
        }

        $this->setLinkData($linkData, $html, 'site', $site->id);

        $site->status = count($links) >= 8 ? 1 : 2;
        $site->link_data_id = $linkData->id;
        $site->save();

        Link::upsert($this->getLinksInsertData($links, $site->id), ['link_url'], ['link_url', 'parent_id']);
    }

    private function getLinksInsertData(array $links, int $parentId): array
    {
        $linksInsertData = [];

        foreach ($links as $link) {
            $linksInsertData[] = [
                'parent_id' => $parentId,
                'link_url' => $link
            ];
        }

        return $linksInsertData;
    }

    private function setLinkData(LinkData $linkData, string $html, string $parentType, int $parentId)
    {
        $linkData->type = '';
        $linkData->meta_title = $this->DOMService->getMetaTitle($html);
        $linkData->meta_description = $this->DOMService->getMetaDescription($html);
        $linkData->meta_keywords = $this->DOMService->getMetaKeywords($html);
        $linkData->h_tags = json_encode($this->DOMService->getHTags($html));
        $linkData->img_alts = json_encode($this->DOMService->getImgAlts($html));
        $linkData->href_titles = json_encode($this->DOMService->getHrefTitles($html));

        if ($parentType === 'site') {
            $linkData->parent_site_id = $parentId;
        }

        if ($parentType === 'link') {
            $linkData->parent_link_id = $parentId;
        }

        $linkData->save();
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
