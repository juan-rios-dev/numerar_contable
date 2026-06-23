<?php

namespace Numerar\Contable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Numerar\Contable\Traits\HasAuditFields;
use Numerar\Contable\Traits\HasTenancy;

class AccountingEntryType extends Model
{
    use HasAuditFields, HasTenancy;

    protected $table = 'accounting_entry_types';

    protected $fillable = [
        'code',
        'name',
        'is_closing',
        'is_system',
        'active',
    ];

    protected $casts = [
        'is_closing' => 'boolean',
        'is_system'  => 'boolean',
        'active'     => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────

    public function sequences(): HasMany
    {
        return $this->hasMany(AccountingEntrySequence::class, 'entry_type_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class, 'entry_type', 'code');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    // ── Helpers ───────────────────────────────────────────────

    public function defaultSequence(): ?AccountingEntrySequence
    {
        return $this->sequences()->active()->orderBy('priority')->first();
    }

    public static function findByCode(string $code): ?static
    {
        return static::where('code', $code)->first();
    }
}
