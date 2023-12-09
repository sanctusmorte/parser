<?php

namespace App\Observers;

use App\Jobs\DeleteMaskIdsForLinksJob;
use App\Jobs\UpdateMasksForAllSitesJob;
use App\Models\LinksMask;
use App\Services\Masks\MasksService;
use App\Services\Sites\SitesService;

class LinksMaskObserver
{
    private SitesService $sitesService;

    public function __construct(SitesService $sitesService)
    {
        $this->sitesService = $sitesService;
    }

    /**
     * Handle the LinksMask "created" event.
     *
     * @param  \App\Models\LinksMask  $linksMask
     * @return void
     */
    public function created(LinksMask $linksMask)
    {
        DeleteMaskIdsForLinksJob::dispatch($linksMask->id);
    }

    /**
     * Handle the LinksMask "updated" event.
     *
     * @param  \App\Models\LinksMask  $linksMask
     * @return void
     */
    public function updated(LinksMask $linksMask)
    {
        DeleteMaskIdsForLinksJob::dispatch($linksMask->id);

    }

    /**
     * Handle the LinksMask "deleted" event.
     *
     * @param  \App\Models\LinksMask  $linksMask
     * @return void
     */
    public function deleted(LinksMask $linksMask)
    {
       DeleteMaskIdsForLinksJob::dispatch($linksMask->id);

    }

    /**
     * Handle the LinksMask "restored" event.
     *
     * @param  \App\Models\LinksMask  $linksMask
     * @return void
     */
    public function restored(LinksMask $linksMask)
    {
        //
    }

    /**
     * Handle the LinksMask "force deleted" event.
     *
     * @param  \App\Models\LinksMask  $linksMask
     * @return void
     */
    public function forceDeleted(LinksMask $linksMask)
    {
        //
    }
}
