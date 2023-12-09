<?php

namespace App\Providers;

use App\Actions\FirstParseAction;
use App\Actions\SeeLinksMasksAction;
use App\FormFields\LinkFormField;
use Illuminate\Support\ServiceProvider;
use Illuminate\Events\Dispatcher;
use TCG\Voyager\Facades\Voyager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Voyager::addAction(FirstParseAction::class);
        Voyager::addAction(SeeLinksMasksAction::class);
        Voyager::addFormField(LinkFormField::class);
    }
}
