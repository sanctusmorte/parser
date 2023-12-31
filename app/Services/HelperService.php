<?php

namespace App\Services;

class HelperService
{
    static public function divideTextBySeparators(string $text)
    {
        $data = [];

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

        return array_values($data);
    }
}
