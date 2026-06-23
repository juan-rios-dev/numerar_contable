<?php

namespace Numerar\Contable\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Numerar\Contable\Enums\AccountNature;
use Numerar\Contable\Enums\AccountType;
use Numerar\Contable\Enums\EntryStatus;
use Numerar\Contable\Traits\HasAuditFields;
use Numerar\Contable\Traits\HasTenancy;

class Account extends Model
{
    use HasAuditFields, HasTenancy;

    protected $table = 'accounts';

    protected $fillable = [
        'parent_id',
        'class_id',
        'code',
        'name',
        'description',
        'nature',
        'account_type',
        'active',
    ];

    protected $casts = [
        'nature'       => AccountNature::class,
        'account_type' => AccountType::class,
        'active'       => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────

    public function class(): BelongsTo
    {
        return $this->belongsTo(AccountClass::class, 'class_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingEntryLine::class, 'account_id');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeMovement($query)
    {
        return $query->where('account_type', AccountType::MOVIMIENTO);
    }

    public function scopeMayor($query)
    {
        return $query->where('account_type', AccountType::MAYOR);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOfClass($query, int|array $classId)
    {
        return $query->whereIn('class_id', (array) $classId);
    }

    // ── Jerarquía ─────────────────────────────────────────────

    /**
     * Retorna todos los descendientes de esta cuenta (BFS).
     * Carga eager de 'children' en cada nivel para evitar N+1.
     */
    public function descendants(): Collection
    {
        $all      = new Collection();
        $children = $this->children()->with('children')->get();

        while ($children->isNotEmpty()) {
            $all = $all->merge($children);
            $children = $children->flatMap(fn ($account) => $account->children);
        }

        return $all;
    }

    public function descendantIds(): array
    {
        return $this->descendants()->pluck('id')->toArray();
    }

    public function depth(): int
    {
        $depth  = 0;
        $parent = $this->parent;

        while ($parent !== null) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function isLeaf(): bool
    {
        return $this->children()->doesntExist();
    }

    // ── Saldos ────────────────────────────────────────────────

    /**
     * Saldo propio: movimientos directos en esta cuenta dentro del rango.
     * Positivo = saldo en dirección de la naturaleza.
     */
    public function ownBalance(?string $dateFrom = null, ?string $dateTo = null): float
    {
        [$debits, $credits] = $this->sumDebitsCredits([$this->id], $dateFrom, $dateTo);

        return $this->nature->netBalance($debits, $credits);
    }

    /**
     * Saldo consolidado: movimientos propios + todos los descendientes.
     */
    public function consolidatedBalance(?string $dateFrom = null, ?string $dateTo = null): float
    {
        $ids = array_merge([$this->id], $this->descendantIds());

        [$debits, $credits] = $this->sumDebitsCredits($ids, $dateFrom, $dateTo);

        return $this->nature->netBalance($debits, $credits);
    }

    /**
     * Saldo inicial: suma de movimientos ANTES de $dateFrom.
     * Usado por el Libro Mayor y Balance de Prueba.
     */
    public function openingBalance(string $dateFrom): float
    {
        [$debits, $credits] = $this->sumDebitsCredits([$this->id], null, null, before: $dateFrom);

        return $this->nature->netBalance($debits, $credits);
    }

    // ── Helpers internos ──────────────────────────────────────

    private function sumDebitsCredits(
        array   $accountIds,
        ?string $dateFrom = null,
        ?string $dateTo   = null,
        ?string $before   = null,
    ): array {
        $query = AccountingEntryLine::query()
            ->whereIn('account_id', $accountIds)
            ->join('accounting_entries', 'accounting_entries.id', '=', 'accounting_entry_lines.entry_id')
            ->where('accounting_entries.status', EntryStatus::POSTED->value);

        if ($before) {
            $query->whereDate('accounting_entries.date', '<', $before);
        } else {
            if ($dateFrom) {
                $query->whereDate('accounting_entries.date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('accounting_entries.date', '<=', $dateTo);
            }
        }

        $debits  = (float) (clone $query)->sum('accounting_entry_lines.debit');
        $credits = (float) (clone $query)->sum('accounting_entry_lines.credit');

        return [$debits, $credits];
    }

    // ── Helpers de UI ─────────────────────────────────────────

    public function isDebitNature(): bool
    {
        return $this->nature === AccountNature::DEBIT;
    }

    public function isCreditNature(): bool
    {
        return $this->nature === AccountNature::CREDIT;
    }

    public function isMovement(): bool
    {
        return $this->account_type === AccountType::MOVIMIENTO;
    }

    public function isMayor(): bool
    {
        return $this->account_type === AccountType::MAYOR;
    }

    public function getFullNameAttribute(): string
    {
        return $this->code ? "[{$this->code}] {$this->name}" : $this->name;
    }
}
