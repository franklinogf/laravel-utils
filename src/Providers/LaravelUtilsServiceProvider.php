<?php

declare(strict_types=1);

namespace Franklinogf\LaravelUtils\Providers;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;

final class LaravelUtilsServiceProvider extends ServiceProvider
{
    /**
     * The commands to be registered.
     *
     * @var list<class-string<Command>>
     */
    private array $commands = [
        \Franklinogf\LaravelUtils\Commands\SyncEnumsCommand::class,
        \Franklinogf\LaravelUtils\Commands\ExportLangKeysCommand::class,
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);

            $this->publishes([
                __DIR__.'/../../config/utils.php' => config_path('utils.php'),
            ], 'config');
        }
    }
}
