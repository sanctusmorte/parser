<?php

namespace App\Services\TextTemplate;

use App\Services\HelperService;
use App\Services\TextTemplate\Enum\DictionaryTypeEnum;
use Illuminate\Support\Facades\DB;

class TextTemplateService
{
    public function findDictionaryMatchesForTextAsArray(array $textItems, string $dictionaryType)
    {
        $db = DB::table($dictionaryType);

        foreach ($textItems as $textItem) {
            $db->orWhereRaw('LOWER(name) LIKE "%'.strtolower($textItem).'%"');
        }

        return $db->pluck('id')->toArray();
    }

    public function parseAllTextTemplateForText(string $text): array
    {
        $textTemplates = [];

        $textItems = HelperService::divideTextBySeparators($text);
        $needDictionaries = [
            DictionaryTypeEnum::TAGKEY,
            DictionaryTypeEnum::ADULTKEY,
            DictionaryTypeEnum::VIDEOKEY,
            DictionaryTypeEnum::PORNSTARS,
        ];

        foreach ($needDictionaries as $needDictionary) {
            $matches = $this->findDictionaryMatchesForTextAsArray($textItems, $needDictionary);
            ksort($matches);
            $textTemplates[str_replace('text_template_', '', $needDictionary)] = $matches;
        }

        return $textTemplates;
    }


}
