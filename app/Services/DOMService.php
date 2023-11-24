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

    public function getAllLinks(string $html, string $domain, $logs)
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
                $childrens = $link->getChildren() ?? [];
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
                    $href = 'https://' . $domain . $href;
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

        return [$filteredLinks, $logs];
    }
}
