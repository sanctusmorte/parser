<?php

namespace App\Services\Parse;

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
    const MODEL_NOT_FOUND_ERROR = '[ID:%s][PARSE_SITE] Ошибка парсинга, сайт не найден в БД';
    const BAD_RESPONSE_ERROR = '[ID:%s][PARSE_SITE] Ошибка парсинга - %s';
    const DEBUG_START = '[PARSE_SITE] Начали парсить сайт [ID:%s]';
    const DEBUG_GOT_HTML = '[PARSE_SITE] Успешно спарсили, разбираем HTML [ID:%s]';
    const DEBUG_READY_TO_SAVE = '[PARSE_SITE] Разобрали HTML, вытащили данные, сохраняем [ID:%s]';

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
    public function parse(int $siteId): void
    {
        try {
            $this->startParseSite($siteId);
        } catch (ModelNotFoundException) {
            Log::error(sprintf(self::MODEL_NOT_FOUND_ERROR, $siteId));
        } catch (HttpClientException|ParseSiteBadResponseException $e) {
            $trace = array_slice($e->getTrace(), 0, 3);;
            Log::error(sprintf(self::BAD_RESPONSE_ERROR, $siteId, $e->getMessage()), $trace);
        } catch (Exception $e) {
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

        Log::debug(sprintf(self::DEBUG_START, $siteId));

        try {
            $response = $this->guzzleService->getRequest($existSite->full_domain);
        } catch (HttpClientException $e) {
            throw new HttpClientException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$response->ok()) {
            throw new ParseSiteBadResponseException($response->body(), $response->status());
        }

        Log::debug(sprintf(self::DEBUG_GOT_HTML, $siteId));

        $html = $response->body();
        $response->close();

        $this->setDataToSite($html, $existSite);

        return 1;
    }

    private function setDataToSite(string $html, Site $site)
    {
        $links = $this->DOMService->getAllLinks($html, $site->domain);

        $site->status = count($links) >= 10 ? 1 : 2;
        $site->type = '';
        $site->links = json_encode($links);
        $site->meta_title = $this->DOMService->getMetaTitle($html);
        $site->meta_description = $this->DOMService->getMetaDescription($html);
        $site->meta_keywords = $this->DOMService->getMetaKeywords($html);
        $site->h_tags = json_encode($this->DOMService->getHTags($html));
        $site->img_alts = json_encode($this->DOMService->getImgAlts($html));
        $site->href_titles = json_encode($this->DOMService->getHrefTitles($html));

        Log::debug(sprintf(self::DEBUG_READY_TO_SAVE, $site->id));

        $site->save();
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
