<?php

namespace App\Services;

use App\Models\Site;
use App\Models\SiteDetail;
use Illuminate\Support\Facades\Log;

class ParseSiteService
{
    private GuzzleService $guzzleService;
    private DOMService $DOMService;

    public function __construct(GuzzleService $guzzleService, DOMService $DOMService)
    {
        $this->guzzleService = $guzzleService;
        $this->DOMService = $DOMService;
    }

    public function firstParse(int $siteId)
    {
        $existSite = Site::find($siteId);
        $data = [];
        $logs = [];
        $type = '';

        if (!is_null($existSite)) {

            $url = $existSite->full_domain;
            $domain = $existSite->domain;

            $logs[] = 'Начинаем парсинг сайта ' . $url;
            $response = $this->guzzleService->getRequest($url);

            if ($response->ok()) {
                $html = $response->body();
                $response->close();

                $logs[] = 'Главную страницу успешно спарсили, ищем ссылки';

                [$links, $logs] = $this->DOMService->getAllLinks($html, $domain, $logs);

                if (count($links) >= 15) {
                    $data = $this->checkLinks($links);

                    $redirectCount = count($data['redirect']);
                    $notRedirectCount = count($data['not_redirect']);

                    $type = 'Tube';

                    if ($notRedirectCount === 0 && $redirectCount === 0) {
                        $type = 'Unknown';
                    }

                    if ($redirectCount === 0 && $notRedirectCount > 0) {
                        $type = 'CJ';
                    }

                    if ($redirectCount > 0) {
                        if (round($redirectCount / $notRedirectCount, 2) > 0.4) {
                            $type = 'CJ/Tube';
                        }
                    }

                    $logs[] = 'Спарсили все 15 ссылок, с редиректом - ' . $redirectCount . ', без редикрета - ' . $notRedirectCount . ', ошибок - ' . count($data['errors']);
                }
            } else {
                $logs[] = 'Ошибка! Не смогли спарсить главную страницу';
            }

            $saveLinks = [];

            foreach ($data as $dataLinks) {
                $saveLinks = array_merge($saveLinks, $dataLinks);
            }

            $existSite->is_first_parsed = 1;
            $existSite->links = implode(PHP_EOL, $saveLinks);
            $existSite->logs = implode(PHP_EOL, $logs);
            $existSite->type = $type;
            $existSite->save();
        }
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
