<?php

namespace Numerar\Contable\Enums;

enum PeriodStatus: string
{
    case OPEN   = 'OPEN';
    case CLOSED = 'CLOSED';
    case LOCKED = 'LOCKED';

    public function label(): string
    {
        return match($this) {
            self::OPEN   => 'Abierto',
            self::CLOSED => 'Cerrado',
            self::LOCKED => 'Bloqueado (ejercicio cerrado)',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::OPEN   => 'green',
            self::CLOSED => 'gray',
            self::LOCKED => 'red',
        };
    }

    public function isOpen(): bool   { return $this === self::OPEN; }
    public function isClosed(): bool { return $this === self::CLOSED; }
    public function isLocked(): bool { return $this === self::LOCKED; }
}
