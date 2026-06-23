<?php

namespace Numerar\Contable\Exceptions;

use RuntimeException;

class AccountingException extends RuntimeException
{
    public static function make(string $message): static
    {
        return new static($message);
    }
}
