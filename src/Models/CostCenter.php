<?php

namespace Numerar\Contable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Numerar\Contable\Traits\HasAuditFields;
use Numerar\Contable\Traits\HasTenancy;

class CostCenter extends Model
{
    use HasAuditFields, HasTenancy;

    protected $table = 'cost_centers';

    protected $fillable = [
        'code',
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingEntryLine::class, 'cost_center_id');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
