<?php

namespace Numerar\Contable\Console\Commands;

use Illuminate\Console\Command;
use Numerar\Contable\Database\Seeders\AccountClassSeeder;
use Numerar\Contable\Database\Seeders\EntryTypeSeeder;
use Numerar\Contable\Database\Seeders\PucSeeder;

class InstallCommand extends Command
{
    protected $signature = 'contable:install
                            {--fresh : Elimina y recrea las tablas del módulo (¡destructivo!)}
                            {--no-seed : Omite los datos iniciales (clases PUC y tipos de comprobante)}
                            {--with-puc : Siembra el catálogo completo de cuentas PUC colombiano}';

    protected $description = 'Instala el módulo contable: publica config, corre migraciones y siembra datos iniciales';

    public function handle(): int
    {
        $this->components->info('Instalando módulo contable Numerar Contable...');

        // 1. Publicar config
        $this->publishConfig();

        // 2. Publicar assets (CSS)
        $this->publishAssets();

        // 3. Publicar y correr migraciones
        $this->runMigrations();

        // 3. Datos iniciales
        if (! $this->option('no-seed')) {
            $this->seedInitialData();
        }

        // 4. PUC completo (opcional)
        if ($this->option('with-puc')) {
            $this->seedPuc();
        }

        $this->newLine();
        $this->components->info('¡Módulo contable instalado correctamente!');
        $this->newLine();
        $this->line('  Próximos pasos:');
        $this->line('  1. Revisa <comment>config/contable.php</comment> y ajusta middleware, prefijos y tenancy.');
        $this->line('  2. <comment>Terceros:</comment> por defecto se usa la tabla `terceros` incluida.');
        $this->line('     Para usar tus propios modelos (clientes, empleados, etc.), establece:');
        $this->line('       <comment>\'use_terceros_table\' => false</comment>  y agrega tus modelos en el array <comment>\'terceros\'</comment>.');
        if (! $this->option('with-puc')) {
            $this->line('  * Para cargar el catálogo completo de cuentas PUC colombiano:');
            $this->line('    <comment>php artisan contable:install --with-puc --no-seed</comment>');
        }
        $this->line('  3. Si usas multi-tenancy, registra el resolver en tu AppServiceProvider:');
        $this->line('     <comment>Accounting::resolveTenantUsing(fn() => auth()->user()?->company_id);</comment>');
        $this->line('  4. Si quieres roles, define los gates en tu AuthServiceProvider:');
        $this->line('     <comment>Gate::define(\'contable.access\', fn($u) => $u->hasRole(\'contador\'));</comment>');
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Pasos ─────────────────────────────────────────────────

    private function publishAssets(): void
    {
        $this->components->task('Publicando assets (CSS)', function () {
            $this->callSilently('vendor:publish', [
                '--tag'      => 'contable-assets',
                '--provider' => 'Numerar\Contable\ContableServiceProvider',
                '--force'    => true,
            ]);
            return true;
        });
    }

    private function publishConfig(): void
    {
        $this->components->task('Publicando configuración', function () {
            if (file_exists(config_path('contable.php'))) {
                $this->line('  <comment>config/contable.php ya existe — omitido. Usa --force para sobreescribir.</comment>');
                return true;
            }

            $this->callSilently('vendor:publish', [
                '--tag'      => 'contable-config',
                '--provider' => 'Numerar\Contable\ContableServiceProvider',
            ]);

            return true;
        });
    }

    private function runMigrations(): void
    {
        if ($this->option('fresh')) {
            if (! $this->confirm('¿Seguro? --fresh eliminará todas las tablas del módulo contable.', false)) {
                $this->line('  <comment>--fresh cancelado.</comment>');
            } else {
                $this->components->task('Eliminando tablas existentes', function () {
                    $this->callSilently('migrate:rollback', ['--path' => 'vendor/koneko/accounting/src/Database/Migrations']);
                    return true;
                });
            }
        }

        $this->components->task('Ejecutando migraciones', function () {
            $this->callSilently('migrate', ['--force' => true]);
            return true;
        });
    }

    private function seedPuc(): void
    {
        $this->components->task('Sembrando catálogo PUC colombiano', function () {
            $seeder = new PucSeeder();
            $seeder->setCommand($this);
            $seeder->run();
            return true;
        });
    }

    private function seedInitialData(): void
    {
        $this->components->task('Sembrando clases PUC colombiano (9 clases)', function () {
            $seeder = new AccountClassSeeder();
            $seeder->setCommand($this);
            $seeder->run();
            return true;
        });

        $this->components->task('Sembrando tipos de comprobante', function () {
            $seeder = new EntryTypeSeeder();
            $seeder->setCommand($this);
            $seeder->run();
            return true;
        });
    }
}
