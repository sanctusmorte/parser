<?php

namespace App\Console\Commands;

use App\Jobs\ParseSiteJob;
use App\Services\Sites\SitesDataService;
use Illuminate\Console\Command;

class ParseSitesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:sites';

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
        $ids = $dataService->getLinksToParse();

        foreach ($ids as $siteId) {
            ParseSiteJob::dispatch($siteId);
            sleep(4);
        }

        return Command::SUCCESS;
    }
}
