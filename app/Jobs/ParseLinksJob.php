<?php

namespace App\Jobs;

use App\Services\Parse\ParseSiteService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ParseLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $siteId,
    ) {}


    /**
     * Execute the job.
     *
     * @param ParseSiteService $parseSiteService
     * @return int
     * @throws Exception
     */
    public function handle(ParseSiteService $parseSiteService): int
    {
        try {
            $parseSiteService->parseLink($this->siteId);
        } catch (Exception $e) {
            Log::error('Ошибка парсинга линки', [$e->getMessage(), $e->getTrace()]);
        }

        return 1;
    }
}
