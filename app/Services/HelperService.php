<?php

namespace App\Services;

class HelperService
{
    static public function divideTextBySeparators(string $text)
    {
        $data = [];
        $needData = [];

        foreach ([',', '+', 'and', ' ', '|', '/'] as $separator) {
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

        foreach ($data as $item) {
            foreach ([',', '+', 'and', ' ', '|', '/'] as $separator) {
                if (str_contains($item, $separator)) {
                    $explodeItems = explode($separator, $item);
                    foreach ($explodeItems as $explodeItem) {
                        if (strlen($explodeItem) >= 2) {
                            $explodeItem = str_replace(':', '', $explodeItem);
                            $explodeItem = str_replace('+', '', $explodeItem);
                            $explodeItem = str_replace('~', '', $explodeItem);
                            $explodeItem = trim($explodeItem);
                            if (strlen($explodeItem) < 3) {
                                continue;
                            }
                            preg_match("/^[a-zA-Z0-9]+$/", $explodeItem, $newExplodeItem);
                            if (isset($newExplodeItem[0])) {
                                $newExplodeItemName = $newExplodeItem[0];
                                if (!isset($needData[$newExplodeItemName])) {
                                    $needData[$newExplodeItemName] = $newExplodeItemName;
                                }
                            }
                        }
                    }
                }
            }
        }

        return array_values($needData);
    }
}
