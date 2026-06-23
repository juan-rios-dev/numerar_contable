<?php

namespace Numerar\Contable\Exceptions;

class UnbalancedEntryException extends AccountingException
{
    public static function withDifference(float $difference): static
    {
        $formatted = number_format($difference, 2, ',', '.');
        return new static("El comprobante no está balanceado. Diferencia: \${$formatted}");
    }
}
