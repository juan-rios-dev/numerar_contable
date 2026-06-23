<?php

namespace Numerar\Contable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Numerar\Contable\Enums\AccountNature;
use Numerar\Contable\Traits\HasAuditFields;
use Numerar\Contable\Traits\HasTenancy;

class AccountClass extends Model
{
    use HasAuditFields, HasTenancy;

    protected $table = 'account_classes';

    protected $fillable = [
        'code',
        'name',
        'nature',
        'active',
    ];

    protected $casts = [
        'nature' => AccountNature::class,
        'active' => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'class_id');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    // ── Helpers ───────────────────────────────────────────────

    public function isDebit(): bool
    {
        return $this->nature === AccountNature::DEBIT;
    }

    public function isCredit(): bool
    {
        return $this->nature === AccountNature::CREDIT;
    }
}
