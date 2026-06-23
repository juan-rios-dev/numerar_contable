<?php

namespace Numerar\Contable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Numerar\Contable\Models\AccountingEntrySequence;
use Numerar\Contable\Models\AccountingEntryType;
use Numerar\Contable\Enums\EntryStatus;
use Numerar\Contable\Traits\HasAuditFields;
use Numerar\Contable\Traits\HasTenancy;

class AccountingEntry extends Model
{
    use HasAuditFields, HasTenancy;

    protected $table = 'accounting_entries';

    protected $fillable = [
        'accounting_period_id',
        'entry_number',
        'entry_type',
        'entry_sequence_id',
        'date',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => EntryStatus::class,
        'date'   => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function entryType(): BelongsTo
    {
        return $this->belongsTo(AccountingEntryType::class, 'entry_type', 'code');
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(AccountingEntrySequence::class, 'entry_sequence_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingEntryLine::class, 'entry_id');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopePosted($query)
    {
        return $query->where('status', EntryStatus::POSTED);
    }

    public function scopeVoided($query)
    {
        return $query->where('status', EntryStatus::VOIDED);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('entry_type', $type);
    }

    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);
    }

    // ── Helpers ───────────────────────────────────────────────

    public function isPosted(): bool
    {
        return $this->status === EntryStatus::POSTED;
    }

    public function isVoided(): bool
    {
        return $this->status === EntryStatus::VOIDED;
    }

    public function totalDebits(): float
    {
        return (float) $this->lines->sum('debit');
    }

    public function totalCredits(): float
    {
        return (float) $this->lines->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->totalDebits() - $this->totalCredits()) < 0.001;
    }

    public function difference(): float
    {
        return abs($this->totalDebits() - $this->totalCredits());
    }
}
