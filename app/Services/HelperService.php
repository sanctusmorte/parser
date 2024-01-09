<?php

namespace App\Services;

class HelperService
{
    static public function divideTextBySeparators(string $text)
    {
        $data = [];

        foreach ([',', '+', 'and', ' ', '|', '/', '-'] as $separator) {
            if (str_contains($text, $separator)) {
                $explodeItems = explode($separator, $text);
                foreach ($explodeItems as $explodeItem) {
                    if (strlen($explodeItem) >= 2) {
                        $explodeItem = str_replace(':', '', $explodeItem);
                        $explodeItem = str_replace('+', '', $explodeItem);
                        $explodeItem = str_replace('~', '', $explodeItem);
                        $explodeItem = trim($explodeItem);
                        if (!isset($data[$explodeItem])) {
                            $data[$explodeItem] = $explodeItem;
                        }
                    }
                }
            }
        }

        return array_values($data);
    }

    static public function divideTextInArray(array $items)
    {
        $data = [];

        foreach ($items as $item) {
            $words = self::divideTextBySeparators($item);
            if (count($words) > 0) {
                foreach ($words as $word) {
                    $itemName = strtolower($word);
                    if (strlen($itemName) < 3) {
                        continue;
                    }
                    $itemName = str_replace(' ', '', $itemName);
                    if (!isset($data[$itemName])) {
                        $data[$itemName] = $itemName;
                    }
                }
            }
            if (strlen($item) < 3) {
                continue;
            }
            $item = str_replace(' ', '', $item);
            if (!isset($data[$item])) {
                $data[$item] = $item;
            }
        }

        return $data;
    }
}
