<?php

namespace Numerar\Contable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Numerar\Contable\Enums\PeriodStatus;
use Numerar\Contable\Traits\HasAuditFields;
use Numerar\Contable\Traits\HasTenancy;

class AccountingPeriod extends Model
{
    use HasAuditFields, HasTenancy;

    protected $table = 'accounting_periods';

    protected $fillable = [
        'year',
        'month',
        'start_date',
        'end_date',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'year'       => 'integer',
        'month'      => 'integer',
        'start_date' => 'date',
        'end_date'   => 'date',
        'status'     => PeriodStatus::class,
        'opened_at'  => 'datetime',
        'closed_at'  => 'datetime',
    ];

    // ── Relaciones ────────────────────────────────────────────

    public function entries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class, 'accounting_period_id');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', PeriodStatus::OPEN);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', PeriodStatus::CLOSED);
    }

    public function scopeLocked($query)
    {
        return $query->where('status', PeriodStatus::LOCKED);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    // ── Estado ────────────────────────────────────────────────

    public function isOpen(): bool   { return $this->status === PeriodStatus::OPEN; }
    public function isClosed(): bool { return $this->status === PeriodStatus::CLOSED; }
    public function isLocked(): bool { return $this->status === PeriodStatus::LOCKED; }

    public function close(?int $userId = null): bool
    {
        if ($this->isClosed() || $this->isLocked()) {
            return false;
        }

        return $this->update([
            'status'     => PeriodStatus::CLOSED,
            'closed_at'  => now(),
            'updated_by' => $userId,
        ]);
    }

    public function reopen(?int $userId = null): bool
    {
        if ($this->isOpen() || $this->isLocked()) {
            return false;
        }

        return $this->update([
            'status'     => PeriodStatus::OPEN,
            'opened_at'  => now(),
            'closed_at'  => null,
            'updated_by' => $userId,
        ]);
    }

    public function lock(?int $userId = null): bool
    {
        return $this->update([
            'status'     => PeriodStatus::LOCKED,
            'closed_at'  => $this->closed_at ?? now(),
            'updated_by' => $userId,
        ]);
    }

    public function unlock(?int $userId = null): bool
    {
        if (! $this->isLocked()) {
            return false;
        }

        return $this->update([
            'status'     => PeriodStatus::OPEN,
            'opened_at'  => now(),
            'closed_at'  => null,
            'updated_by' => $userId,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────

    public function containsDate(\DateTimeInterface|string $date): bool
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;

        return $date->between($this->start_date, $this->end_date);
    }

    public function getNameAttribute(): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return ($months[$this->month] ?? $this->month) . ' ' . $this->year;
    }
}
