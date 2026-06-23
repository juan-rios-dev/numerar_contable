<?php

namespace Numerar\Contable;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Numerar\Contable\Console\Commands\InstallCommand;
use Numerar\Contable\Services\AccountingService;

class ContableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/contable.php',
            'contable'
        );

        $this->app->singleton('contable', fn () => new AccountingService());
    }

    public function boot(): void
    {
        $this->registerPublishables();
        $this->registerViews();
        $this->registerMigrations();
        $this->registerRoutes();
        $this->registerGates();
    }

    // ── Publishables ──────────────────────────────────────────

    protected function registerPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([InstallCommand::class]);

        $this->publishes([
            __DIR__ . '/../config/contable.php' => config_path('contable.php'),
        ], 'contable-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations/' => database_path('migrations'),
        ], 'contable-migrations');

        $this->publishes([
            __DIR__ . '/../resources/dist/' => public_path('vendor/accounting'),
        ], 'contable-assets');

        $this->publishes([
            __DIR__ . '/../resources/views/accounting/' => resource_path('views/vendor/accounting'),
        ], 'contable-views');
    }

    // ── Views ─────────────────────────────────────────────────

    protected function registerViews(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/../resources/views/accounting',
            'contable'
        );
    }

    // ── Migrations ────────────────────────────────────────────

    protected function registerMigrations(): void
    {
        $migrationsPath = __DIR__ . '/Database/Migrations';

        if (config('contable.use_terceros_table', true)) {
            $this->loadMigrationsFrom($migrationsPath);
            return;
        }

        // Cargar todas las migraciones excepto la tabla terceros
        $files = glob($migrationsPath . '/*.php');

        $filtered = array_filter($files, fn ($f) => ! str_contains(basename($f), 'terceros'));

        $this->loadMigrationsFrom($filtered);
    }

    // ── Routes ────────────────────────────────────────────────

    protected function registerRoutes(): void
    {
        if (config('contable.features.web', true)) {
            $this->app['router']->group([
                'prefix'     => config('contable.web_prefix', 'contable'),
                'middleware' => config('contable.web_middleware', ['web', 'auth']),
                'as'         => config('contable.web_as', 'contable.'),
            ], function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
        }

        if (config('contable.features.api', true)) {
            $this->app['router']->group([
                'prefix'     => config('contable.api_prefix', 'api/accounting'),
                'middleware' => config('contable.api_middleware', ['api', 'auth:sanctum']),
                'as'         => config('contable.api_as', 'api.accounting.'),
            ], function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });
        }
    }

    // ── Gates ─────────────────────────────────────────────────

    protected function registerGates(): void
    {
        $gates = config('contable.gates', []);

        foreach ($gates as $ability) {
            // Solo registra el gate si el consuming app NO lo definió ya.
            // Si no lo define nadie, por defecto permite (true).
            if (! Gate::has($ability)) {
                Gate::define($ability, fn () => true);
            }
        }
    }
}
