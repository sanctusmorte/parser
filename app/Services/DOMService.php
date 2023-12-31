<?php

namespace App\Services;

use Mockery\Exception;
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

    public function getHrefTitles(string $html, string $linkUrl): array
    {
        $hrefTitles = [];

        $html = $this->prepareHtml($html);
        $dom = new Dom;
        $dom->loadStr($html);
        $result = $dom->find('a')->toArray();
        $usedHrefs = [];
        $usedTitles = [];

        if (count($result) > 0) {
            foreach ($result as $htmlNode) {
                $title = $htmlNode->getTag()->getAttribute('title')['value'] ?? null;

                if (is_null($title)) {
                    $parent = $htmlNode->getParent();
                    foreach ($parent->find('p')->toArray() as $parentP) {
                        if (strlen($parentP->text) >= 5) {
                            $title = $parentP->text;
                            break;
                        };
                    }
                }

                $href = $htmlNode->getTag()->getAttribute('href')['value'] ?? null;

                if (is_null($title) or is_null($href)) {
                    continue;
                }

                if ($href === $linkUrl or $href === $linkUrl . '/') {
                    continue;
                }

                $title = substr(trim($title), 0, 100);
                $title = preg_replace('/[0-9]+/', '', $title);
                $title = str_replace(':', '', $title);
                $title = str_replace('Movies', '', $title);
                $title = trim($title);

                if (strlen($title) < 3) {
                    continue;
                }


                $needTitles = [];

//                foreach ([',', '+', 'and', ' '] as $separator) {
//                    if (str_contains($title, $separator)) {
//                        $explodeItems = explode($separator, $title);
//                        foreach ($explodeItems as $explodeItem) {
//                            if (strlen($explodeItem) >= 2) {
//                                $explodeItem = str_replace(':', '', $explodeItem);
//                                $explodeItem = str_replace('+', '', $explodeItem);
//                                $explodeItem = trim($explodeItem);
//                                $needTitles[] = $explodeItem;
//                            }
//                        }
//                    }
//                }

                $needTitles[] = $title;

                foreach ($needTitles as $title) {
                    if (!isset($usedHrefs[$href])) {
                        $usedHrefs[$href] = 1;
                        if (!isset($usedTitles[$title])) {
                            $hrefTitles[] = $title;
                            $usedTitles[$title] = 1;
                        }
                    }
                }
            }
        }

//        foreach ($hrefTitles as $hrefTitleKey => $hrefTitle) {
//            if (str_contains($hrefTitle, ' ')) {
//                $explodeItems = explode(' ', $hrefTitle);
//                foreach ($explodeItems as $explodeItem) {
//                    $hrefTitles[] = $explodeItem;
//                }
//                unset($hrefTitles[$hrefTitleKey]);
//            }
//        }

        return $hrefTitles;
    }

    public function getAllQueryLinks(string $html)
    {
        $data = [];

        $html = $this->prepareHtml($html);
        $dom = new Dom;
        $dom->loadStr($html);
        $links = $dom->find('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (str_contains($href, 'query')) {
                $data[] = $href;
                if (count($data) > 5) {
                    return $data;
                }
            }
        }

        return $data;
    }

    private function getUniqueLinks(array $links)
    {
        $data = [];
        $needLinks = [];

        foreach ($links as $link) {
            $items = explode('/', $link['path_url']);
            if (!isset($items[1])) {
                continue;
            }
            $group = $items[1];
            if ($group == '') {
                continue;
            }

            if (!isset($needLinks[$group])) {
                $needLinks[$group][] = $link;
            } else {
                if (count($needLinks[$group]) < 5) {
                    $needLinks[$group][] = $link;
                }
            }
        }

        foreach ($needLinks as $needLink) {
            foreach ($needLink as $item) {
                $data[] = $item;
            }
        }

        if (count($data) > 15) {
            $data = array_slice($data, 0, 15);
        }

        return $data;
    }

    public function getAllLinks(string $html, string $domain)
    {
        $html = $this->prepareHtml($html);

        $dom = new Dom;
        $dom->loadStr($html);
        $links = $dom->find('a');

        if (count($links) > 500) {
            $links = array_slice($links->toArray(), 0, 500);
        }

        $invalid = [];
        $invalid[] = 'https://' . $domain;
        $invalid[] = 'https://' . $domain . '/';
        $invalid[] = 'http://' . $domain;
        $invalid[] = 'http://' . $domain . '/';

        $needLinks = [];

        if (!empty($links)) {
            foreach ($links as $link) {

                $href = $link->getAttribute('href');
                if (in_array($href, $invalid)) {
                    continue;
                }

                $title = null;
                $anotherLinks = $dom->find('a[href="'.$href.'"]')->toArray();

                foreach ($anotherLinks as $anotherLink) {
                    $anotherLinkText = trim($anotherLink->text);
                    if ($anotherLink->id() !== $link->id() && strlen($anotherLinkText) > 3) {
                        $title = $anotherLinkText;
                    }
                }

                if (!str_contains($href, 'https://') && !str_contains($href, 'http://')) {
                    if (str_starts_with($href, '/')) {
                        $href = 'https://' . $domain . $href;
                    } else {
                        $href = 'https://' . $domain . '/' . $href;
                    }
                }

                if (count($link->find('img')) > 0 && $this->isHrefContainsDomain($href, $domain)) {
                    if (is_null($title)) {
                        $title = $link->getAttribute('title');
                    }
                    if (is_null($title)) {
                        $title = $this->findTextInHtmlNode($link);
                    }
                    if (is_null($title)) {
                        $siblingsLinks = $link->getParent()->find('a')->toArray();
                        foreach ($siblingsLinks as $siblingsLink) {
                            if ($siblingsLink->id() !== $link->id()) {
                                $title = $this->findTextInHtmlNode($siblingsLink);
                            }
                        }
                    }
                    $pathUrl = substr($href, strpos($href, $domain) + strlen($domain));
                    if ($pathUrl === '/' or $pathUrl === '') {
                        continue;
                    }
                    $needLinks[$link->id()] = [
                        'href' => $href,
                        'path_url' => $pathUrl,
                        'title' => $title
                    ];
                }
            }
        }

        return $this->getUniqueLinks($needLinks);
    }

    private function findTextInHtmlNode(Dom\HtmlNode $node)
    {
        $text = null;

        $pTitle = $node->find('p')->toArray()[0] ?? null;
        if (!is_null($pTitle) and strlen($pTitle->text) > 2) {
            $text = $pTitle->text;
        }
        $spanTitle = $node->find('span')->toArray()[0] ?? null;
        if (!is_null($spanTitle) and strlen($spanTitle->text) > 2) {
            $text = $spanTitle->text;
        }
        $strongTitle = $node->find('strong')->toArray()[0] ?? null;
        if (!is_null($strongTitle) and strlen($strongTitle->text) > 2) {
            $text = $strongTitle->text;
        }
        $bTitle = $node->find('b')->toArray()[0] ?? null;
        if (!is_null($bTitle) and strlen($bTitle->text) > 2) {
            $text = $bTitle->text;
        }
        $h3Title = $node->find('h3')->toArray()[0] ?? null;
        if (!is_null($h3Title) and strlen($h3Title->text) > 2) {
            $text = $h3Title->text;
        }

        if (is_null($text)) {
            $text = $node->text;
        }
        
        return $text;
    }

    private function isHrefContainsDomain(string $href, string $domain): bool
    {
        if (str_contains($href, $domain)) {
            return true;
        }

        $items = explode('.', $domain);

        foreach ($items as $item) {
            if (str_contains($href, $item)) {
                return true;
            }
        }

        return false;
    }
}
