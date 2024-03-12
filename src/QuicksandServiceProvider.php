<?php

namespace Tighten\Quicksand;

use Illuminate\Support\ServiceProvider;

class QuicksandServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/quicksand.php' => config_path('quicksand.php'),
        ]);
    }

    public function register()
    {
        $this->app->singleton(DeleteOldSoftDeletes::class);
        $this->commands([DeleteOldSoftDeletes::class]);
    }
}
