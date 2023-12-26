<?php

namespace App\Services\Sites;

use App\Jobs\HandleSiteMasksJob;
use App\Jobs\ParseLinksJob;
use App\Models\Link;
use App\Models\LinkData;
use App\Models\Site;
use App\Models\SiteTextTemplate;
use App\Services\HelperService;
use App\Services\TextTemplate\Enum\DictionaryTypeEnum;
use App\Services\TextTemplate\TextTemplateService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class SitesService
{
    private TextTemplateService $textTemplateService;

    public function __construct(TextTemplateService $textTemplateService)
    {
        $this->textTemplateService = $textTemplateService;
    }

    public function updateMasksForAllSites()
    {
       $sites = Site::where('status', 1)->pluck('id')->toArray();

        foreach ($sites as $siteId) {
            HandleSiteMasksJob::dispatch($siteId);
        }
    }

    public function parseAndSaveTextTemplateForSiteById(int $siteId)
    {
        $site = Site::find($siteId);

        if (is_null($site)) {
            throw new ModelNotFoundException();
        }

        $siteData = $site->siteData();
        $metaTitle = $siteData->meta_title;

        $textTemplateJson = json_encode($this->textTemplateService->parseAllTextTemplateForText($metaTitle));
        $existTextTemplate = SiteTextTemplate::where('template', $textTemplateJson)->firstOrCreate();
        $existTextTemplate->template = $textTemplateJson;
        $existTextTemplate->save();

        $site->text_template_id = $existTextTemplate->id;
        $site->is_text_template_parsed = 1;

        $site->save();
    }

    public function deleteMaskIdsForAllLinks(int $maskId)
    {
        $updateData = [];
        $links = Link::whereJsonContains('mask_ids', $maskId)->get();

        foreach ($links as $link) {
            $updateData[] = [
                'id' => $link->id,
                'mask_ids' => null
            ];
        }

        Link::upsert($updateData, ['id'], ['mask_ids']);

        $this->updateMasksForAllSites();
    }
}
