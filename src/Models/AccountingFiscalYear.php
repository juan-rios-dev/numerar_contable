<?php

namespace Numerar\Contable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Numerar\Contable\Traits\HasAuditFields;
use Numerar\Contable\Traits\HasTenancy;

class AccountingFiscalYear extends Model
{
    use HasAuditFields, HasTenancy;

    protected $table = 'accounting_fiscal_years';

    protected $fillable = [
        'year',
        'status',
        'closing_entry_id',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'year'       => 'integer',
        'opened_at'  => 'datetime',
        'closed_at'  => 'datetime',
    ];

    // ── Relaciones ────────────────────────────────────────────

    public function closingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'closing_entry_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AccountingPeriod::class, 'year', 'year');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'CLOSED');
    }

    // ── Estado ────────────────────────────────────────────────

    public function isOpen(): bool   { return $this->status === 'OPEN'; }
    public function isClosed(): bool { return $this->status === 'CLOSED'; }

    // ── Helper estático ───────────────────────────────────────

    public static function findOrCreateForYear(int $year): static
    {
        return static::firstOrCreate(
            ['year' => $year],
            ['status' => 'OPEN', 'opened_at' => now()]
        );
    }
}
