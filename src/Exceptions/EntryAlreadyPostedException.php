<?php

namespace Numerar\Contable\Exceptions;

class EntryAlreadyPostedException extends AccountingException
{
    public static function forEntry(string $entryNumber): static
    {
        return new static("El comprobante '{$entryNumber}' ya está contabilizado y no puede modificarse.");
    }
}
