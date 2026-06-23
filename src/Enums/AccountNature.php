<?php

namespace Numerar\Contable\Enums;

enum AccountNature: string
{
    case DEBIT  = 'DEBIT';
    case CREDIT = 'CREDIT';

    public function label(): string
    {
        return match($this) {
            self::DEBIT  => 'Débito',
            self::CREDIT => 'Crédito',
        };
    }

    public function opposite(): self
    {
        return match($this) {
            self::DEBIT  => self::CREDIT,
            self::CREDIT => self::DEBIT,
        };
    }

    /** Calcula el saldo neto dado débitos y créditos acumulados */
    public function netBalance(float $debits, float $credits): float
    {
        return match($this) {
            self::DEBIT  => $debits - $credits,
            self::CREDIT => $credits - $debits,
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::DEBIT  => 'blue',
            self::CREDIT => 'green',
        };
    }
}
