<?php

namespace Numerar\Contable\Enums;

enum AccountType: string
{
    case MAYOR      = 'MAYOR';
    case MOVIMIENTO = 'MOVIMIENTO';

    public function label(): string
    {
        return match($this) {
            self::MAYOR      => 'Mayor',
            self::MOVIMIENTO => 'Movimiento',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::MAYOR      => 'gray',
            self::MOVIMIENTO => 'indigo',
        };
    }
}
