<?php

namespace App\Jobs;

use App\Services\Parse\ParseSiteService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FirstParseSiteJob implements ShouldQueue
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
            $parseSiteService->parse($this->siteId);
        } catch (Exception $e) {
            throw new Exception();
        }

        return 1;
    }
}
