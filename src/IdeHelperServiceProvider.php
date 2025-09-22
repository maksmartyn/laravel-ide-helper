<?php

namespace MaksMartyn\LaravelIdeHelper;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class IdeHelperServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(
            'command.ide-helper.models-from-migrations',
            function ($app) {
                return new Console\ModelsFromMigrationsCommand($app['files']);
            }
        );

        $this->commands('command.ide-helper.models-from-migrations');
    }
}