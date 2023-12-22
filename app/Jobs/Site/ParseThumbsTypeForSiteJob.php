<?php

namespace App\Jobs\Site;

use App\Services\Thumbs\ThumbsService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ParseThumbsTypeForSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $siteId,
    ) {}

    /**
     * Execute the job.
     *
     * @param ThumbsService $thumbsService
     * @return int
     */
    public function handle(ThumbsService $thumbsService): int
    {
        try {
            $thumbsService->parseThumbsTypeForSite($this->siteId);
        } catch (Exception $e) {
            Log::error('Ошибка парсинга thumbs type для сайта [ID - ' . $this->siteId . ']', [$e->getMessage(), $e->getTrace()]);
        }

        return 1;
    }
}
