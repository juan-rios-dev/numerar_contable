<?php

namespace Numerar\Contable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Numerar\Contable\Traits\HasAuditFields;
use Numerar\Contable\Traits\HasTenancy;

class AccountingEntrySequence extends Model
{
    use HasAuditFields, HasTenancy;

    protected $table = 'accounting_entry_sequences';

    protected $fillable = [
        'entry_type_id',
        'name',
        'prefix',
        'initial_number',
        'priority',
        'active',
    ];

    protected $casts = [
        'initial_number' => 'integer',
        'priority'       => 'integer',
        'active'         => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────

    public function entryType(): BelongsTo
    {
        return $this->belongsTo(AccountingEntryType::class, 'entry_type_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class, 'entry_sequence_id');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    // ── Numeración ────────────────────────────────────────────

    /**
     * Calcula el siguiente número consecutivo para el año dado.
     * Nunca reutiliza números: cuenta todos los comprobantes del año
     * (incluyendo anulados) para garantizar unicidad.
     */
    public function nextNumber(int $year): int
    {
        $used = AccountingEntry::where('entry_sequence_id', $this->id)
            ->whereYear('date', $year)
            ->count();

        return $this->initial_number + $used;
    }

    public function formatNumber(int $year): string
    {
        return $this->prefix . $year . '-' . str_pad($this->nextNumber($year), 4, '0', STR_PAD_LEFT);
    }
}
