<?php

namespace Numerar\Contable\Exceptions;

class PeriodClosedException extends AccountingException
{
    public static function forPeriod(string $periodName): static
    {
        return new static("El periodo '{$periodName}' está cerrado y no acepta movimientos.");
    }
}
