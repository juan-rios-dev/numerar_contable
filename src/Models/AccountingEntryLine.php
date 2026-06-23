<?php

namespace Numerar\Contable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Numerar\Contable\Traits\HasTenancy;

class AccountingEntryLine extends Model
{
    use HasTenancy;

    protected $table = 'accounting_entry_lines';

    protected $fillable = [
        'entry_id',
        'account_id',
        'description',
        'debit',
        'credit',
        'third_party_id',
        'third_party_type',
        'cost_center_id',
    ];

    protected $casts = [
        'debit'  => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    // ── Relaciones ────────────────────────────────────────────

    public function entry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function thirdParty(): MorphTo
    {
        return $this->morphTo(null, 'third_party_type', 'third_party_id');
    }

    public function getThirdPartyNameAttribute(): string
    {
        if (! $this->third_party_id || ! $this->thirdParty) {
            return '—';
        }

        $config = collect(config('contable.terceros', []))
            ->firstWhere('model', get_class($this->thirdParty));

        $attr = $config['display_attribute'] ?? 'name';

        return $this->thirdParty->{$attr} ?? '—';
    }

    // ── Helpers ───────────────────────────────────────────────

    public function isDebit(): bool
    {
        return (float) $this->debit > 0;
    }

    public function isCredit(): bool
    {
        return (float) $this->credit > 0;
    }

    public function amount(): float
    {
        return $this->isDebit() ? (float) $this->debit : (float) $this->credit;
    }

    public function hasThirdParty(): bool
    {
        return ! is_null($this->third_party_id);
    }

    public function hasCostCenter(): bool
    {
        return ! is_null($this->cost_center_id);
    }
}
