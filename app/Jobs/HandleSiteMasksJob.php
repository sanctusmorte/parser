<?php

namespace App\Jobs;

use App\Services\Masks\MasksService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HandleSiteMasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $siteId,
    ) {}

    /**
     * Execute the job.
     *
     * @param MasksService $service
     * @return int
     */
    public function handle(MasksService $service): int
    {
        try {
            $service->handleMasksForSite($this->siteId);
        } catch (Exception $e) {
            Log::error('Ошибка группировки масок для сайта - ' . $this->siteId . ']', [$e->getMessage(), $e->getTrace()]);
        }

        return 1;
    }
}
