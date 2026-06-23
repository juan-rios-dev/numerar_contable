<?php

namespace Numerar\Contable\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Numerar\Contable\Enums\PeriodStatus;
use Numerar\Contable\Exceptions\AccountingException;
use Numerar\Contable\Models\Account;
use Numerar\Contable\Models\AccountClass;
use Numerar\Contable\Models\AccountingFiscalYear;
use Numerar\Contable\Models\AccountingPeriod;

class FiscalYearService
{
    public function __construct(
        protected EntryService $entries = new EntryService(),
    ) {}

    // ══════════════════════════════════════════════════════════
    // CÁLCULO DEL RESULTADO
    // ══════════════════════════════════════════════════════════

    /**
     * Calcula la utilidad o pérdida del año.
     * Solo considera cuentas de movimiento activas de clases 4, 5, 6 y 7.
     */
    public function calculateNetResult(int $year): array
    {
        $yearStart = "{$year}-01-01";
        $yearEnd   = "{$year}-12-31";

        $resultClassIds = AccountClass::whereIn('code', ['4', '5', '6', '7'])->pluck('id');

        $accounts = Account::movement()
            ->active()
            ->whereIn('class_id', $resultClassIds)
            ->with('class')
            ->get();

        $lines         = [];
        $totalIncome   = 0.0;
        $totalExpenses = 0.0;

        foreach ($accounts as $account) {
            $balance = $account->ownBalance($yearStart, $yearEnd);

            if (abs($balance) < 0.001) {
                continue;
            }

            $classCode = $account->class->code;

            if ($classCode === '4') {
                // Clase 4 (Ingresos): naturaleza crédito → saldo positivo = CR → para zerear: DR
                $totalIncome += $balance;
                $lines[] = [
                    'account_id'  => $account->id,
                    'account'     => $account,
                    'debit'       => $balance > 0 ? $balance  : 0,
                    'credit'      => $balance < 0 ? abs($balance) : 0,
                    'description' => 'Cierre ' . $account->name,
                ];
            } else {
                // Clases 5, 6, 7 (Gastos/Costos): naturaleza débito → saldo positivo = DR → para zerear: CR
                $totalExpenses += $balance;
                $lines[] = [
                    'account_id'  => $account->id,
                    'account'     => $account,
                    'debit'       => $balance < 0 ? abs($balance) : 0,
                    'credit'      => $balance > 0 ? $balance  : 0,
                    'description' => 'Cierre ' . $account->name,
                ];
            }
        }

        $net      = $totalIncome - $totalExpenses;
        $isProfit = $net >= 0;

        return [
            'income'    => $totalIncome,
            'expenses'  => $totalExpenses,
            'net'       => $net,
            'is_profit' => $isProfit,
            'lines'     => $lines,
        ];
    }

    // ══════════════════════════════════════════════════════════
    // CIERRE DE EJERCICIO
    // ══════════════════════════════════════════════════════════

    /**
     * Cierra el ejercicio fiscal de un año:
     *  1. Genera el asiento de cierre en el último período del año.
     *  2. Bloquea todos los períodos del año.
     *  3. Registra el ejercicio como CLOSED.
     *
     * @param  array{entry_type: string, entry_sequence_id: ?int, equity_account_id: int} $data
     */
    public function close(int $year, array $data): AccountingFiscalYear
    {
        return DB::transaction(function () use ($year, $data) {
            // findOrCreate fuera del lock para no bloquear en INSERT
            AccountingFiscalYear::findOrCreateForYear($year);

            // Lock exclusivo: si otro proceso está cerrando el mismo año,
            // esta línea espera hasta que la transacción anterior termine.
            $fiscalYear = AccountingFiscalYear::where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fiscalYear->isClosed()) {
                throw AccountingException::make(
                    "El ejercicio {$year} ya está cerrado."
                );
            }

            $periods = AccountingPeriod::forYear($year)->orderBy('month')->get();

            if ($periods->isEmpty()) {
                throw AccountingException::make(
                    "No existen períodos contables registrados para el año {$year}."
                );
            }

            $equityAccount = Account::findOrFail($data['equity_account_id']);

            if (! $equityAccount->active) {
                throw AccountingException::make(
                    "La cuenta de patrimonio seleccionada está inactiva."
                );
            }

            $result    = $this->calculateNetResult($year);
            $lastPeriod = $periods->last();
            $entryDate  = $lastPeriod->end_date->toDateString();

            $closingLines = $result['lines'];

            // Línea de la cuenta de patrimonio
            $net = $result['net'];
            if (abs($net) >= 0.001) {
                $closingLines[] = [
                    'account_id'  => $equityAccount->id,
                    'debit'       => $net < 0 ? abs($net) : 0,   // pérdida → DR patrimonio
                    'credit'      => $net > 0 ? $net       : 0,  // utilidad → CR patrimonio
                    'description' => $net >= 0
                        ? "Utilidad del ejercicio {$year}"
                        : "Pérdida del ejercicio {$year}",
                ];
            }

            if (count($closingLines) < 2) {
                throw AccountingException::make(
                    "No hay movimientos en cuentas de resultado para el año {$year}. No se puede generar el asiento de cierre."
                );
            }

            $entry = $this->entries->createInPeriod($lastPeriod, [
                'entry_type'        => $data['entry_type'],
                'entry_sequence_id' => $data['entry_sequence_id'] ?? null,
                'date'              => $entryDate,
                'description'       => "Cierre del ejercicio {$year}",
                'lines'             => $closingLines,
            ]);

            // Bloquear los períodos existentes y crear como LOCKED los meses que nunca se abrieron
            $existingMonths = $periods->pluck('month')->flip();

            AccountingPeriod::forYear($year)->each(fn ($p) => $p->lock());

            foreach (range(1, 12) as $month) {
                if (! $existingMonths->has($month)) {
                    $start = Carbon::create($year, $month, 1)->startOfMonth();
                    AccountingPeriod::create([
                        'year'       => $year,
                        'month'      => $month,
                        'start_date' => $start->toDateString(),
                        'end_date'   => $start->copy()->endOfMonth()->toDateString(),
                        'status'     => PeriodStatus::LOCKED->value,
                        'closed_at'  => now(),
                    ]);
                }
            }

            $fiscalYear->update([
                'status'           => 'CLOSED',
                'closing_entry_id' => $entry->id,
                'closed_at'        => now(),
            ]);

            return $fiscalYear->fresh('closingEntry');
        });
    }

    // ══════════════════════════════════════════════════════════
    // REAPERTURA DE EJERCICIO
    // ══════════════════════════════════════════════════════════

    /**
     * Reabre un ejercicio cerrado:
     *  1. Anula el asiento de cierre.
     *  2. Desbloquea todos los períodos del año.
     *  3. Marca el ejercicio como OPEN.
     */
    public function reopen(int $year): AccountingFiscalYear
    {
        return DB::transaction(function () use ($year) {
            $fiscalYear = AccountingFiscalYear::where('year', $year)->firstOrFail();

            if ($fiscalYear->isOpen()) {
                throw AccountingException::make(
                    "El ejercicio {$year} ya está abierto."
                );
            }

            if ($fiscalYear->closing_entry_id) {
                $this->entries->void($fiscalYear->closingEntry);
            }

            // Desbloquear todos los períodos del año
            AccountingPeriod::forYear($year)->each(fn ($p) => $p->unlock());

            $fiscalYear->update([
                'status'           => 'OPEN',
                'closing_entry_id' => null,
                'closed_at'        => null,
            ]);

            return $fiscalYear->fresh();
        });
    }

    // ══════════════════════════════════════════════════════════
    // CONSULTAS
    // ══════════════════════════════════════════════════════════

    public function getForYear(int $year): ?AccountingFiscalYear
    {
        return AccountingFiscalYear::where('year', $year)->first();
    }

    public function allYears(): \Illuminate\Database\Eloquent\Collection
    {
        return AccountingFiscalYear::orderByDesc('year')->get();
    }
}
