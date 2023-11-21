<?php

namespace App\Jobs;

use App\Services\ParseSiteService;
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
     * @return void
     */
    public function handle(ParseSiteService $parseSiteService)
    {
        $parseSiteService->firstParse($this->siteId);
    }
}
