<?php

namespace Quicksand;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class QuicksandServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(RunForceDeletePolicy::class);
    }
}
