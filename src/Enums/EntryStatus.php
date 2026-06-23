<?php

namespace Numerar\Contable\Enums;

enum EntryStatus: string
{
    case POSTED = 'POSTED';
    case VOIDED = 'VOIDED';

    public function label(): string
    {
        return match($this) {
            self::POSTED => 'Contabilizado',
            self::VOIDED => 'Anulado',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::POSTED => 'green',
            self::VOIDED => 'red',
        };
    }

    public function isPosted(): bool { return $this === self::POSTED; }
    public function isVoided(): bool { return $this === self::VOIDED; }
}
