<?php

namespace Numerar\Contable\Database\Seeders;

use Illuminate\Database\Seeder;
use Numerar\Contable\Models\AccountingEntrySequence;
use Numerar\Contable\Models\AccountingEntryType;

class EntryTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'CI',  'name' => 'Comprobante de Ingreso',   'is_closing' => false],
            ['code' => 'CE',  'name' => 'Comprobante de Egreso',    'is_closing' => false],
            ['code' => 'CD',  'name' => 'Comprobante Diario',       'is_closing' => false],
            ['code' => 'CA',  'name' => 'Comprobante de Ajuste',    'is_closing' => false],
            ['code' => 'CO',  'name' => 'Comprobante de Compra',    'is_closing' => false],
            ['code' => 'NC',  'name' => 'Nota de Contabilidad',     'is_closing' => false],
            ['code' => 'CIE', 'name' => 'Comprobante de Cierre',    'is_closing' => true],
        ];

        foreach ($types as $data) {
            $type = AccountingEntryType::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name'       => $data['name'],
                    'is_closing' => $data['is_closing'],
                    'is_system'  => true,
                    'active'     => true,
                ]
            );

            AccountingEntrySequence::firstOrCreate(
                ['entry_type_id' => $type->id, 'priority' => 1],
                [
                    'name'           => $data['name'],
                    'prefix'         => $data['code'] . '-',
                    'initial_number' => 1,
                    'active'         => true,
                ]
            );
        }

        $this->command?->info('  ✓ ' . count($types) . ' tipos de comprobante insertados/actualizados.');
    }
}
