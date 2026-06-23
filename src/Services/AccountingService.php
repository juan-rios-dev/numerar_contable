<?php

namespace Numerar\Contable\Services;

use Closure;
use Numerar\Contable\Enums\EntryStatus;
use Numerar\Contable\Enums\PeriodStatus;
use Numerar\Contable\Models\AccountingFiscalYear;
use Numerar\Contable\Exceptions\AccountingException;
use Numerar\Contable\Models\Account;
use Numerar\Contable\Models\AccountClass;
use Numerar\Contable\Models\AccountingEntry;
use Numerar\Contable\Models\AccountingPeriod;
use Numerar\Contable\Models\CostCenter;

class AccountingService
{
    protected static ?Closure $tenantResolver = null;

    public static function resolveTenantUsing(Closure $callback): void
    {
        static::$tenantResolver = $callback;
    }

    public function resolveTenant(): int|string|null
    {
        if (static::$tenantResolver === null) {
            return null;
        }

        return (static::$tenantResolver)();
    }

    public function __construct(
        protected EntryService      $entries     = new EntryService(),
        protected ReportService     $reports     = new ReportService(),
        protected FiscalYearService $fiscalYears = new FiscalYearService(),
    ) {}

    // ══════════════════════════════════════════════════════════
    // COMPROBANTES
    // ══════════════════════════════════════════════════════════

    public function createEntry(array $data): AccountingEntry
    {
        return $this->entries->create($data);
    }

    public function updateEntry(int|AccountingEntry $entry, array $data): AccountingEntry
    {
        $entry = $entry instanceof AccountingEntry
            ? $entry
            : AccountingEntry::findOrFail($entry);

        return $this->entries->update($entry, $data);
    }

    public function voidEntry(int|AccountingEntry $entry): AccountingEntry
    {
        $entry = $entry instanceof AccountingEntry
            ? $entry
            : AccountingEntry::findOrFail($entry);

        return $this->entries->void($entry);
    }

    public function deleteEntry(int|AccountingEntry $entry): bool
    {
        $entry = $entry instanceof AccountingEntry
            ? $entry
            : AccountingEntry::findOrFail($entry);

        return $this->entries->delete($entry);
    }

    // ══════════════════════════════════════════════════════════
    // PERIODOS
    // ══════════════════════════════════════════════════════════

    public function createPeriod(array $data): AccountingPeriod
    {
        $fiscalYear = AccountingFiscalYear::where('year', $data['year'])->first();
        if ($fiscalYear && $fiscalYear->isClosed()) {
            throw AccountingException::make(
                "El ejercicio {$data['year']} ya está cerrado. No se pueden crear períodos en un ejercicio cerrado."
            );
        }

        $exists = AccountingPeriod::where('year', $data['year'])
            ->where('month', $data['month'])
            ->exists();

        if ($exists) {
            throw AccountingException::make(
                "Ya existe un periodo para {$data['month']}/{$data['year']}."
            );
        }

        return AccountingPeriod::create([
            'year'       => $data['year'],
            'month'      => $data['month'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'status'     => PeriodStatus::OPEN->value,
            'opened_at'  => now(),
        ]);
    }

    public function closePeriod(int|AccountingPeriod $period): AccountingPeriod
    {
        $period = $period instanceof AccountingPeriod
            ? $period
            : AccountingPeriod::findOrFail($period);

        if ($period->isClosed()) {
            throw AccountingException::make(
                "El periodo '{$period->name}' ya está cerrado."
            );
        }


        $period->close();

        return $period->fresh();
    }

    public function openPeriod(int|AccountingPeriod $period): AccountingPeriod
    {
        $period = $period instanceof AccountingPeriod
            ? $period
            : AccountingPeriod::findOrFail($period);

        if ($period->isOpen()) {
            throw AccountingException::make(
                "El periodo '{$period->name}' ya está abierto."
            );
        }

        $fiscalYear = AccountingFiscalYear::where('year', $period->year)->first();
        if ($fiscalYear && $fiscalYear->isClosed()) {
            throw AccountingException::make(
                "El ejercicio {$period->year} ya está cerrado. No se puede reabrir el período '{$period->name}'."
            );
        }

        $period->reopen();

        return $period->fresh();
    }

    // ══════════════════════════════════════════════════════════
    // CATÁLOGO DE CUENTAS
    // ══════════════════════════════════════════════════════════

    public function createAccount(array $data): Account
    {
        return Account::create($data);
    }

    public function updateAccount(int|Account $account, array $data): Account
    {
        $account = $account instanceof Account ? $account : Account::findOrFail($account);
        $account->update($data);
        return $account->fresh();
    }

    public function toggleAccount(int|Account $account): Account
    {
        $account = $account instanceof Account ? $account : Account::findOrFail($account);
        $account->update(['active' => ! $account->active]);
        return $account->fresh();
    }

    /**
     * Árbol completo de cuentas para una clase.
     * Retorna colección anidada con 'children' recursivos.
     */
    public function accountTree(?int $classId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Account::with('children')
            ->whereNull('parent_id')
            ->orderBy('code');

        if ($classId) {
            $query->where('class_id', $classId);
        }

        return $query->get();
    }

    /**
     * Lista plana de cuentas con indentación de nivel para selects.
     */
    public function accountFlat(?int $classId = null, bool $onlyMovement = false): array
    {
        $query = Account::with('children')
            ->whereNull('parent_id')
            ->orderBy('code');

        if ($classId) {
            $query->where('class_id', $classId);
        }

        $result = [];
        $this->flattenTree($query->get(), $result, 0, $onlyMovement);

        return $result;
    }

    // ══════════════════════════════════════════════════════════
    // CLASES Y CENTROS DE COSTO
    // ══════════════════════════════════════════════════════════

    public function createAccountClass(array $data): AccountClass
    {
        return AccountClass::create($data);
    }

    public function createCostCenter(array $data): CostCenter
    {
        return CostCenter::create($data);
    }

    public function updateCostCenter(int|CostCenter $costCenter, array $data): CostCenter
    {
        $costCenter = $costCenter instanceof CostCenter ? $costCenter : CostCenter::findOrFail($costCenter);
        $costCenter->update($data);
        return $costCenter->fresh();
    }

    // ══════════════════════════════════════════════════════════
    // EJERCICIO FISCAL
    // ══════════════════════════════════════════════════════════

    public function closeFiscalYear(int $year, array $data): AccountingFiscalYear
    {
        return $this->fiscalYears->close($year, $data);
    }

    public function reopenFiscalYear(int $year): AccountingFiscalYear
    {
        return $this->fiscalYears->reopen($year);
    }

    public function fiscalYearNetResult(int $year): array
    {
        return $this->fiscalYears->calculateNetResult($year);
    }

    public function getFiscalYear(int $year): ?AccountingFiscalYear
    {
        return $this->fiscalYears->getForYear($year);
    }

    // ══════════════════════════════════════════════════════════
    // REPORTES (delegados al ReportService — Parte 4)
    // ══════════════════════════════════════════════════════════

    public function journal(array $filters): array
    {
        return $this->reports->journal($filters);
    }

    public function generalLedger(array $filters): array
    {
        return $this->reports->generalLedger($filters);
    }

    public function trialBalance(array $filters): array
    {
        return $this->reports->trialBalance($filters);
    }

    public function balanceSheet(array $filters): array
    {
        return $this->reports->balanceSheet($filters);
    }

    public function incomeStatement(array $filters): array
    {
        return $this->reports->incomeStatement($filters);
    }

    public function costCenterReport(array $filters): array
    {
        return $this->reports->costCenter($filters);
    }

    public function thirdPartyLedger(array $filters): array
    {
        return $this->reports->thirdPartyLedger($filters);
    }

    // ══════════════════════════════════════════════════════════
    // INTERNOS
    // ══════════════════════════════════════════════════════════

    private function flattenTree(
        iterable $accounts,
        array    &$result,
        int      $depth,
        bool     $onlyMovement,
    ): void {
        foreach ($accounts as $account) {
            if ($onlyMovement && $account->isMayor() && $account->children->isNotEmpty()) {
                $this->flattenTree($account->children, $result, $depth + 1, $onlyMovement);
                continue;
            }

            $result[] = [
                'id'       => $account->id,
                'class_id' => $account->class_id,
                'code'     => $account->code,
                'name'     => $account->name,
                'label'    => str_repeat('  ', $depth) . ($account->code ? "[{$account->code}] " : '') . $account->name,
                'depth'    => $depth,
                'nature'   => $account->nature->value,
                'type'     => $account->account_type->value,
            ];

            if ($account->children->isNotEmpty()) {
                $this->flattenTree($account->children, $result, $depth + 1, $onlyMovement);
            }
        }
    }
}
