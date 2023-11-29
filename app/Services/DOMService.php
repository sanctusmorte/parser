<?php

namespace App\Services;

use PHPHtmlParser\Dom;

class DOMService
{
    private function countImgAttributes(string $attr, array $data, int $id): array
    {
        if (!isset($data[$attr])) {
            $data[$attr] = [
                'count' => 1,
                'links_ids' => [$id]
            ];
        } else {
            $data[$attr]['count'] = $data[$attr]['count'] + 1;
            $data[$attr]['links_ids'][] = $id;
        }

        return $data;
    }

    private function prepareHtml(string $html)
    {
        $html = mb_eregi_replace("'\s+>", "'>", utf8_encode($html));
        $html = mb_eregi_replace('"\s+>', '">', $html);

        return $html;
    }

    public function getMetaTitle(string $html): string
    {
        $title = '';

        $html = $this->prepareHtml($html);
        $dom = new Dom;
        $dom->loadStr($html);
        $result = $dom->find('title');

        if ($result->count() > 0) {
            $htmlNode = $result->toArray()[0];
            if (!empty($htmlNode->getChildren())) {
                $title = html_entity_decode($htmlNode->getChildren()[0]->text);
            }
        }

        return substr($title, 0, 255);
    }

    public function getMetaDescription(string $html): string
    {
        $description = '';

        $html = $this->prepareHtml($html);
        $dom = new Dom;
        $dom->loadStr($html);
        $result = $dom->find('meta[name=description]');

        if ($result->count() > 0) {
            $htmlNode = $result->toArray()[0];
            if (!empty($htmlNode->getTag())) {
                $description = html_entity_decode($htmlNode->getTag()->getAttribute('content')['value']) ?? null;
            }
        }

        return substr($description, 0, 5000);
    }

    public function getMetaKeywords(string $html): string
    {
        $keywords = '';

        $html = $this->prepareHtml($html);
        $dom = new Dom;
        $dom->loadStr($html);
        $result = $dom->find('meta[name=keywords]');

        if ($result->count() > 0) {
            $htmlNode = $result->toArray()[0];
            if (!empty($htmlNode->getTag())) {
                $keywords = html_entity_decode($htmlNode->getTag()->getAttribute('content')['value']) ?? null;
            }
        }

        return substr($keywords, 0, 5000);
    }

    public function getHTags(string $html): array
    {
        $hTags = [];

        $html = $this->prepareHtml($html);
        $dom = new Dom;
        $dom->loadStr($html);
        $result = array_merge($dom->find('h1')->toArray(), $dom->find('h2')->toArray(), $dom->find('h3')->toArray());

        if (count($result) > 0) {
            foreach ($result as $htmlNode) {
                $value = $htmlNode->find('text')->toArray();
                $text = empty($value) ? '' : $value[0]->text;
                $text = html_entity_decode($text);
                $text = substr(trim($text), 0, 100);
                if (strlen($text) > 1) {
                    $hTags[$htmlNode->getTag()->name()][] = $text;
                }

            }
        }

        return $hTags;
    }

    public function getImgAlts(string $html): array
    {
        $imgAlts = [];

        $html = $this->prepareHtml($html);
        $dom = new Dom;
        $dom->loadStr($html);
        $result = $dom->find('img')->toArray();

        if (count($result) > 0) {
            foreach ($result as $htmlNode) {
                $value = html_entity_decode($htmlNode->getTag()->getAttribute('alt')['value']);
                $value = substr(trim($value), 0, 100);
                if (strlen($value) > 1) {
                    $imgAlts[] = $value;
                }
            }
        }

        return $imgAlts;
    }

    public function getHrefTitles(string $html): array
    {
        $hrefTitles = [];

        $html = $this->prepareHtml($html);
        $dom = new Dom;
        $dom->loadStr($html);
        $result = $dom->find('a')->toArray();

        if (count($result) > 0) {
            foreach ($result as $htmlNode) {
                $value = substr(trim($htmlNode->getTag()->getAttribute('title')['value']), 0, 100);
                if (strlen($value) > 1) {
                    $hrefTitles[] = substr(trim($htmlNode->getTag()->getAttribute('title')['value']), 0, 100);
                }
            }
        }

        return $hrefTitles;
    }


    public function getAllLinks(string $html, string $domain)
    {
        $filteredLinks = [];

        $html = $this->prepareHtml($html);

        $dom = new Dom;
        $dom->loadStr($html);
        $links = $dom->find('a');

        $logs[] = 'Всего нашли ' . count($links) . ' ссылок на главной странице, теперь отфильтруем.';

        $needLinks = [];

        if (!empty($links)) {
            foreach ($links as $link) {
                $href = $link->getAttribute('href');

                $invalid = [];
                $invalid[] = 'https://' . $domain;
                $invalid[] = 'https://' . $domain . '/';
                $invalid[] = 'http://' . $domain;
                $invalid[] = 'http://' . $domain . '/';

                if (in_array($href, $invalid)) {
                    continue;
                }

                if (!str_contains($href, 'https://') && !str_contains($href, 'http://')) {
                    if (str_starts_with($href, '/')) {
                        $href = 'https://' . $domain . $href;
                    } else {
                        $href = 'https://' . $domain . '/' . $href;
                    }
                }

                if (count($link->find('img')) > 0) {
                    $needLinks[$link->id()] = $href;
                }
            }
        }

        $logs[] = 'Отфильтровали, подходящих ссылок - ' . count($links) . ' штук, теперь выбираем первые 15';

        if (count($needLinks) > 0) {
            foreach ($needLinks as $key => $linksId) {
                if (!isset($filteredLinks[$key])) {
                    $filteredLinks[$key] = $linksId;
                    if (count($filteredLinks) >= 15) {
                        $logs[] = 'Выбрали первые 15, теперь начинаем проверять каждую ссылку - кидаем запрос и смотрим происходит ли редиректит на другой сайт';
                        break;
                    }
                }
            }
        } else {
            $logs[] = 'Нужных ссылок меньше 15, прекращаем парсинг!';
        }

        return $filteredLinks;
    }
}
