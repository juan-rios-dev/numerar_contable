<?php

namespace Numerar\Contable\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountClassSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = app('contable')->resolveTenant() ?? 0;

        $classes = [
            ['code' => '1', 'name' => 'Activo',                           'nature' => 'DEBIT'],
            ['code' => '2', 'name' => 'Pasivo',                           'nature' => 'CREDIT'],
            ['code' => '3', 'name' => 'Patrimonio',                       'nature' => 'CREDIT'],
            ['code' => '4', 'name' => 'Ingresos',                         'nature' => 'CREDIT'],
            ['code' => '5', 'name' => 'Gastos',                           'nature' => 'DEBIT'],
            ['code' => '6', 'name' => 'Costos de Ventas',                 'nature' => 'DEBIT'],
            ['code' => '7', 'name' => 'Costos de Producción u Operación', 'nature' => 'DEBIT'],
            ['code' => '8', 'name' => 'Cuentas de Orden Deudoras',        'nature' => 'DEBIT'],
            ['code' => '9', 'name' => 'Cuentas de Orden Acreedoras',      'nature' => 'CREDIT'],
        ];

        foreach ($classes as $class) {
            DB::table('account_classes')->updateOrInsert(
                ['tenant_id' => $tenantId, 'code' => $class['code']],
                array_merge($class, [
                    'tenant_id'  => $tenantId,
                    'active'     => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command?->info('  ✓ 9 clases PUC colombiano insertadas/actualizadas.');
    }
}
