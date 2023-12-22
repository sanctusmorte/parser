<?php

namespace App\Console\Commands;

use App\Jobs\Site\ParseThumbsTypeForSiteJob;
use App\Services\Sites\SitesDataService;
use Illuminate\Console\Command;

class ParseThumbsTypeForSitesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:thumb-types-for-site';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(SitesDataService $dataService)
    {
        $ids = $dataService->getSitesWithoutThumbsType();

        foreach ($ids as $siteId) {
            ParseThumbsTypeForSiteJob::dispatch($siteId);
            sleep(12);
        }

        return Command::SUCCESS;
    }
}
