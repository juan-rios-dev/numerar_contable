<?php

namespace Numerar\Contable\Enums;

enum EntryType: string
{
    case CI = 'CI';
    case CE = 'CE';
    case CD = 'CD';
    case CA = 'CA';
    case CC = 'CC';
    case NC = 'NC';

    public function label(): string
    {
        return match($this) {
            self::CI => 'Comprobante de Ingreso',
            self::CE => 'Comprobante de Egreso',
            self::CD => 'Comprobante Diario',
            self::CA => 'Comprobante de Ajuste',
            self::CC => 'Comprobante de Cierre',
            self::NC => 'Nota de Contabilidad',
        };
    }

    public function prefix(): string
    {
        return $this->value . '-';
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::CI => 'green',
            self::CE => 'red',
            self::CD => 'blue',
            self::CA => 'yellow',
            self::CC => 'purple',
            self::NC => 'orange',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases()
        );
    }
}
