<?php

namespace App\Console\Commands;

use App\Jobs\ParseLinksJob;
use App\Services\Links\LinksDataService;
use Illuminate\Console\Command;

class ParseLinksLevelOneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:links';

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
    public function handle(LinksDataService $dataService)
    {
        $ids = $dataService->getLinksToParse();

        foreach ($ids as $linkId) {
            ParseLinksJob::dispatch($linkId);
            sleep(2);
        }

        return Command::SUCCESS;
    }
}
