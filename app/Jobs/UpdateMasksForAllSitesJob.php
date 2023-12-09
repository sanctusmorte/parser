<?php

namespace App\Jobs;

use App\Services\Masks\MasksService;
use App\Services\Sites\SitesService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateMasksForAllSitesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @param MasksService $service
     * @return int
     */
    public function handle(SitesService $service): int
    {
        try {
            $service->updateMasksForAllSites();
        } catch (Exception $e) {

        }

        return 1;
    }
}
