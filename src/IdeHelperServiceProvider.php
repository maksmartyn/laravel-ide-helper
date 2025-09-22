<?php

namespace MaksMartyn\LaravelIdeHelper;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class IdeHelperServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        $this->commands([Console\ModelsFromMigrationsCommand::class]);
    }

    /**
     * @inheritDoc
     */
    public function provides()
    {
        return [Console\ModelsFromMigrationsCommand::class];
    }
}