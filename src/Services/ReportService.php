<?php

namespace Numerar\Contable\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Numerar\Contable\Enums\AccountNature;
use Numerar\Contable\Enums\EntryStatus;
use Numerar\Contable\Models\Account;
use Numerar\Contable\Models\AccountClass;

class ReportService
{
    // ══════════════════════════════════════════════════════════
    // LIBRO DIARIO
    // ══════════════════════════════════════════════════════════

    public function journal(array $filters): array
    {
        $query = DB::table('accounting_entries as ae')
            ->join('accounting_entry_lines as ael', 'ael.entry_id', '=', 'ae.id')
            ->join('accounts as a', 'a.id', '=', 'ael.account_id')
            ->where('ae.status', EntryStatus::POSTED->value)
            ->whereDate('ae.date', '>=', $filters['date_from'])
            ->whereDate('ae.date', '<=', $filters['date_to'])
            ->select(
                'ae.id', 'ae.entry_number', 'ae.entry_type', 'ae.date', 'ae.description as entry_desc',
                'ael.id as line_id', 'ael.description as line_desc',
                'ael.debit', 'ael.credit',
                'a.code as account_code', 'a.name as account_name'
            )
            ->orderBy('ae.date')
            ->orderBy('ae.id')
            ->orderBy('ael.id');

        if (! empty($filters['entry_type'])) {
            $query->where('ae.entry_type', $filters['entry_type']);
        }

        if (! empty($filters['account_id'])) {
            $query->where('ael.account_id', $filters['account_id']);
        }

        $rows = $query->get();

        // Agrupar líneas bajo cada comprobante
        $entries     = [];
        $totalDebit  = 0.0;
        $totalCredit = 0.0;

        foreach ($rows->groupBy('id') as $entryId => $lines) {
            $first       = $lines->first();
            $entryDebit  = (float) $lines->sum('debit');
            $entryCredit = (float) $lines->sum('credit');
            $totalDebit  += $entryDebit;
            $totalCredit += $entryCredit;

            $entries[] = [
                'id'           => $first->id,
                'entry_number' => $first->entry_number,
                'entry_type'   => $first->entry_type,
                'date'         => $first->date,
                'description'  => $first->entry_desc,
                'total_debit'  => $entryDebit,
                'total_credit' => $entryCredit,
                'lines'        => $lines->map(fn ($l) => [
                    'account_code' => $l->account_code,
                    'account_name' => $l->account_name,
                    'description'  => $l->line_desc,
                    'debit'        => (float) $l->debit,
                    'credit'       => (float) $l->credit,
                ])->values()->toArray(),
            ];
        }

        return [
            'filters'      => $filters,
            'entries'      => $entries,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
        ];
    }

    // ══════════════════════════════════════════════════════════
    // LIBRO MAYOR
    // ══════════════════════════════════════════════════════════

    public function generalLedger(array $filters): array
    {
        $account  = Account::findOrFail($filters['account_id']);
        $dateFrom = $filters['date_from'];
        $dateTo   = $filters['date_to'];

        // Saldo inicial: todos los movimientos ANTES de date_from
        [$openDebit, $openCredit] = $this->rawSums([$account->id], before: $dateFrom);
        $openingBalance = $account->nature->netBalance($openDebit, $openCredit);

        // Movimientos del período
        $movements = DB::table('accounting_entry_lines as ael')
            ->join('accounting_entries as ae', 'ae.id', '=', 'ael.entry_id')
            ->where('ael.account_id', $account->id)
            ->where('ae.status', EntryStatus::POSTED->value)
            ->whereDate('ae.date', '>=', $dateFrom)
            ->whereDate('ae.date', '<=', $dateTo)
            ->select('ae.date', 'ae.entry_number', 'ae.entry_type', 'ae.description as entry_desc',
                     'ael.description as line_desc', 'ael.debit', 'ael.credit')
            ->orderBy('ae.date')
            ->orderBy('ae.id')
            ->get();

        $isDebit        = $account->nature === AccountNature::DEBIT;
        $runningBalance = $openingBalance;
        $periodDebit    = 0.0;
        $periodCredit   = 0.0;

        $rows = $movements->map(function ($m) use (&$runningBalance, &$periodDebit, &$periodCredit, $isDebit) {
            $debit  = (float) $m->debit;
            $credit = (float) $m->credit;
            $periodDebit  += $debit;
            $periodCredit += $credit;
            $runningBalance += $isDebit ? ($debit - $credit) : ($credit - $debit);

            return [
                'date'         => $m->date,
                'entry_number' => $m->entry_number,
                'entry_type'   => $m->entry_type,
                'description'  => $m->line_desc ?? $m->entry_desc,
                'debit'        => $debit,
                'credit'       => $credit,
                'balance'      => $runningBalance,
            ];
        })->values()->toArray();

        return [
            'account'         => [
                'id'     => $account->id,
                'code'   => $account->code,
                'name'   => $account->name,
                'nature' => $account->nature->value,
            ],
            'date_from'       => $dateFrom,
            'date_to'         => $dateTo,
            'opening_balance' => $openingBalance,
            'movements'       => $rows,
            'period_debit'    => $periodDebit,
            'period_credit'   => $periodCredit,
            'closing_balance' => $runningBalance,
        ];
    }

    // ══════════════════════════════════════════════════════════
    // BALANCE DE PRUEBA (8 columnas, jerárquico por clase)
    // ══════════════════════════════════════════════════════════

    public function trialBalance(array $filters): array
    {
        $dateFrom     = $filters['date_from'];
        $dateTo       = $filters['date_to'];
        $costCenterId = ! empty($filters['cost_center_id']) ? (int) $filters['cost_center_id'] : null;
        $closeYear    = ! empty($filters['close_year']);

        // El saldo inicial siempre incluye cierres anteriores (arrastre correcto entre años).
        // Los movimientos del período excluyen el comprobante de cierre por defecto;
        // solo se incluye cuando el usuario activa "cierre anual".
        $openingSums = $this->aggregateSums(before: $dateFrom, costCenterId: $costCenterId);
        $periodSums  = $this->aggregateSums(from: $dateFrom, to: $dateTo, costCenterId: $costCenterId, excludeClosing: !$closeYear);

        $classes = AccountClass::orderBy('code')->get();

        $sections    = [];
        $grandTotals = array_fill_keys(['opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit'], 0.0);

        foreach ($classes as $class) {
            $roots = Account::with('children.children.children.children')
                ->where('class_id', $class->id)
                ->whereNull('parent_id')
                ->orderBy('code')
                ->get();

            $nodes = $this->buildTrialBalanceNodes($roots, $openingSums, $periodSums);

            if (empty($nodes)) continue;

            $classOpenDebit  = array_sum(array_column($nodes, '_open_debit'));
            $classOpenCredit = array_sum(array_column($nodes, '_open_credit'));
            $classPerDebit   = array_sum(array_column($nodes, '_per_debit'));
            $classPerCredit  = array_sum(array_column($nodes, '_per_credit'));

            $openingNet = $class->nature->netBalance($classOpenDebit, $classOpenCredit);
            $closingNet = $class->nature->netBalance(
                $classOpenDebit + $classPerDebit,
                $classOpenCredit + $classPerCredit
            );
            [$sOpenDebit, $sOpenCredit] = $this->splitBalance($openingNet, $class->nature);
            [$sClosDebit, $sClosCredit] = $this->splitBalance($closingNet, $class->nature);

            $sectionTotals = [
                'opening_debit'  => $sOpenDebit,
                'opening_credit' => $sOpenCredit,
                'period_debit'   => $classPerDebit,
                'period_credit'  => $classPerCredit,
                'closing_debit'  => $sClosDebit,
                'closing_credit' => $sClosCredit,
            ];

            $sections[] = [
                'class_code' => $class->code,
                'class_name' => $class->name,
                'nodes'      => $nodes,
                'totals'     => $sectionTotals,
            ];
        }

        foreach ($sections as $section) {
            foreach ($section['totals'] as $key => $value) {
                $grandTotals[$key] += $value;
            }
        }

        return [
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
            'cost_center_id' => $costCenterId,
            'close_year'     => $closeYear,
            'sections'       => $sections,
            'totals'         => $grandTotals,
            'balanced'       => $costCenterId ? null : [
                'opening' => abs($grandTotals['opening_debit'] - $grandTotals['opening_credit']) < 0.01,
                'period'  => abs($grandTotals['period_debit']  - $grandTotals['period_credit'])  < 0.01,
                'closing' => abs($grandTotals['closing_debit'] - $grandTotals['closing_credit']) < 0.01,
            ],
        ];
    }

    /**
     * Construye nodos del árbol con los 8 valores del balance de prueba,
     * consolidando recursivamente desde los hijos hacia el padre.
     *
     * Devuelve también _open_debit/_open_credit/_per_debit/_per_credit como
     * sumas RAW consolidadas, para que el nodo padre las acumule correctamente
     * antes de aplicar netBalance/splitBalance.
     */
    private function buildTrialBalanceNodes(
        Collection $accounts,
        Collection $openingSums,
        Collection $periodSums,
    ): array {
        $nodes = [];

        foreach ($accounts as $account) {
            $os = $openingSums->get($account->id);
            $ps = $periodSums->get($account->id);

            $ownOpenDebit  = (float) ($os->total_debit  ?? 0);
            $ownOpenCredit = (float) ($os->total_credit ?? 0);
            $ownPerDebit   = (float) ($ps->total_debit  ?? 0);
            $ownPerCredit  = (float) ($ps->total_credit ?? 0);

            $children = $account->relationLoaded('children')
                ? $this->buildTrialBalanceNodes($account->children, $openingSums, $periodSums)
                : [];

            // Acumular sumas RAW de los hijos (ya consolidadas por cada hijo)
            $consOpenDebit  = $ownOpenDebit  + array_sum(array_column($children, '_open_debit'));
            $consOpenCredit = $ownOpenCredit + array_sum(array_column($children, '_open_credit'));
            $consPerDebit   = $ownPerDebit   + array_sum(array_column($children, '_per_debit'));
            $consPerCredit  = $ownPerCredit  + array_sum(array_column($children, '_per_credit'));

            // Omitir nodos sin dato alguno
            if ($consOpenDebit == 0 && $consOpenCredit == 0
                && $consPerDebit == 0 && $consPerCredit == 0) {
                continue;
            }

            // Saldo inicial: neto según naturaleza → columna deudor o acreedor
            $openingNet = $account->nature->netBalance($consOpenDebit, $consOpenCredit);
            [$openingDebit, $openingCredit] = $this->splitBalance($openingNet, $account->nature);

            // Saldo final: aplica sobre el acumulado opening + período
            $closingNet = $account->nature->netBalance(
                $consOpenDebit + $consPerDebit,
                $consOpenCredit + $consPerCredit
            );
            [$closingDebit, $closingCredit] = $this->splitBalance($closingNet, $account->nature);

            $nodes[] = [
                'id'             => $account->id,
                'code'           => $account->code,
                'name'           => $account->name,
                'nature'         => $account->nature->value,
                // Sumas RAW para que el padre las acumule sin aplicar netBalance dos veces
                '_open_debit'    => $consOpenDebit,
                '_open_credit'   => $consOpenCredit,
                '_per_debit'     => $consPerDebit,
                '_per_credit'    => $consPerCredit,
                // Columnas de visualización (8 columnas)
                'opening_debit'  => $openingDebit,
                'opening_credit' => $openingCredit,
                'period_debit'   => $consPerDebit,   // movimientos: RAW sin netBalance
                'period_credit'  => $consPerCredit,
                'closing_debit'  => $closingDebit,
                'closing_credit' => $closingCredit,
                'children'       => $children,
            ];
        }

        return $nodes;
    }

    // ══════════════════════════════════════════════════════════
    // BALANCE GENERAL (Clases 1, 2, 3)
    // ══════════════════════════════════════════════════════════

    public function balanceSheet(array $filters): array
    {
        $asOf    = $filters['as_of_date'];
        $sums    = $this->aggregateSums(to: $asOf);
        $classes = AccountClass::whereIn('code', ['1', '2', '3'])->orderBy('code')->get();

        $sections     = [];
        $totalAssets  = 0.0;
        $totalLiabPat = 0.0;

        foreach ($classes as $class) {
            $roots = Account::with('children.children.children.children')
                ->where('class_id', $class->id)
                ->whereNull('parent_id')
                ->orderBy('code')
                ->get();

            $nodes        = $this->buildNodes($roots, $sums);
            $sectionTotal = array_sum(array_column($nodes, 'balance'));

            $sections[] = [
                'class_code'  => $class->code,
                'class_name'  => $class->name,
                'nature'      => $class->nature->value,
                'nodes'       => $nodes,
                'total'       => $sectionTotal,
            ];

            if ($class->code === '1') {
                $totalAssets = $sectionTotal;
            } else {
                $totalLiabPat += $sectionTotal;
            }
        }

        // Resultado del período: saldo neto de clases 4–7 aún no cerrado a clase 3.
        // Por partida doble: Activo = Pasivo + Patrimonio + Resultado.
        // Ingresos (Cl.4, naturaleza CREDIT) suman al resultado.
        // Gastos y costos (Cl.5, 6, 7, naturaleza DEBIT) lo restan.
        $periodResult  = $this->computePeriodResult($sums);
        $totalLiabPat += $periodResult;

        return [
            'as_of_date'     => $asOf,
            'sections'       => $sections,
            'period_result'  => $periodResult,
            'total_assets'   => $totalAssets,
            'total_liab_pat' => $totalLiabPat,
            'balanced'       => abs($totalAssets - $totalLiabPat) < 0.01,
        ];
    }

    /**
     * Resultado neto de las cuentas de resultados (clases 4, 5, 6, 7)
     * usando los mismos $sums ya filtrados por fecha.
     * Positivo = utilidad, negativo = pérdida.
     */
    private function computePeriodResult(Collection $sums): float
    {
        $classMap = AccountClass::whereIn('code', ['4', '5', '6', '7'])
            ->pluck('id', 'code');

        // Clase 4 (Ingresos, CREDIT): balance positivo suma al resultado.
        // Clases 5, 6, 7 (Gastos/Costos, DEBIT): balance positivo resta al resultado.
        $signs  = ['4' => 1, '5' => -1, '6' => -1, '7' => -1];
        $result = 0.0;

        foreach ($signs as $code => $sign) {
            $classId = $classMap->get($code);
            if (! $classId) continue;

            $roots = Account::with('children.children.children.children')
                ->where('class_id', $classId)
                ->whereNull('parent_id')
                ->get();

            $nodes   = $this->buildNodes($roots, $sums);
            $result += $sign * array_sum(array_column($nodes, 'balance'));
        }

        return $result;
    }

    // ══════════════════════════════════════════════════════════
    // ESTADO DE RESULTADOS (Clases 4, 5, 6, 7)
    // ══════════════════════════════════════════════════════════

    public function incomeStatement(array $filters): array
    {
        $dateFrom = $filters['date_from'];
        $dateTo   = $filters['date_to'];
        $sums     = $this->aggregateSums(from: $dateFrom, to: $dateTo);

        $classIds = AccountClass::whereIn('code', ['4', '5', '6', '7'])->pluck('id', 'code');

        $rootsFor = function (string $code) use ($classIds): Collection {
            $id = $classIds[$code] ?? null;
            if (! $id) return collect();
            return Account::with('children.children.children.children')
                ->where('class_id', $id)
                ->whereNull('parent_id')
                ->orderBy('code')
                ->get();
        };

        $section = function (Collection $roots, string $name) use ($sums): array {
            $nodes = $this->buildNodes($roots, $sums);
            return [
                'name'  => $name,
                'nodes' => $nodes,
                'total' => (float) array_sum(array_column($nodes, 'balance')),
            ];
        };

        $roots4 = $rootsFor('4');
        $roots5 = $rootsFor('5');

        // Clase 4: operacionales (41) vs otros ingresos (42+)
        $incomeOp    = $section($roots4->filter(fn($a) => str_starts_with((string) $a->code, '41')), 'Ingresos Operacionales');
        $incomeOther = $section($roots4->filter(fn($a) => ! str_starts_with((string) $a->code, '41')), 'Otros Ingresos');

        // Clase 5: admin (51), ventas (52), no operacionales (53), impuesto renta (54/59)
        $expAdmin = $section($roots5->filter(fn($a) => str_starts_with((string) $a->code, '51')), 'Gastos Operacionales de Administración');
        $expSales = $section($roots5->filter(fn($a) => str_starts_with((string) $a->code, '52')), 'Gastos Operacionales de Ventas');
        $expNonOp = $section($roots5->filter(fn($a) => str_starts_with((string) $a->code, '53')), 'Gastos No Operacionales');
        $expTax   = $section($roots5->filter(fn($a) => in_array(substr((string) $a->code, 0, 2), ['54', '59'])), 'Impuesto de Renta y Complementarios');

        // Clase 6 y 7
        $costSales = $section($rootsFor('6'), 'Costo de Ventas y de Prestación de Servicios');
        $costProd  = $section($rootsFor('7'), 'Costos de Producción u Operación');

        $grossProfit     = $incomeOp['total'] - $costSales['total'];
        $operatingProfit = $grossProfit - $expAdmin['total'] - $expSales['total'] - $costProd['total'];
        $preTaxProfit    = $operatingProfit + $incomeOther['total'] - $expNonOp['total'];
        $netProfit       = $preTaxProfit - $expTax['total'];

        return [
            'date_from'        => $dateFrom,
            'date_to'          => $dateTo,
            'income_op'        => $incomeOp,
            'income_other'     => $incomeOther,
            'cost_sales'       => $costSales,
            'cost_prod'        => $costProd,
            'exp_admin'        => $expAdmin,
            'exp_sales'        => $expSales,
            'exp_non_op'       => $expNonOp,
            'exp_tax'          => $expTax,
            'gross_profit'     => $grossProfit,
            'operating_profit' => $operatingProfit,
            'pre_tax_profit'   => $preTaxProfit,
            'net_profit'       => $netProfit,
            // backwards compat
            'sections'         => [
                'income'             => array_merge($incomeOp,   ['class_code' => '4', 'class_name' => 'Ingresos']),
                'expenses'           => array_merge($expAdmin,   ['class_code' => '5', 'class_name' => 'Gastos']),
                'cost_of_sales'      => array_merge($costSales,  ['class_code' => '6', 'class_name' => 'Costos de Ventas']),
                'cost_of_production' => array_merge($costProd,   ['class_code' => '7', 'class_name' => 'Costos de Producción']),
            ],
            'gross_profit'     => $grossProfit,
            'operating_result' => $operatingProfit,
        ];
    }

    // ══════════════════════════════════════════════════════════
    // CENTRO DE COSTO
    // ══════════════════════════════════════════════════════════

    public function costCenter(array $filters): array
    {
        $query = DB::table('accounting_entry_lines as ael')
            ->join('accounting_entries as ae', 'ae.id', '=', 'ael.entry_id')
            ->join('accounts as a', 'a.id', '=', 'ael.account_id')
            ->join('cost_centers as cc', 'cc.id', '=', 'ael.cost_center_id')
            ->where('ae.status', EntryStatus::POSTED->value)
            ->whereNotNull('ael.cost_center_id')
            ->select(
                'cc.id as cc_id', 'cc.code as cc_code', 'cc.name as cc_name',
                'a.code as account_code', 'a.name as account_name',
                DB::raw('SUM(ael.debit) as total_debit'),
                DB::raw('SUM(ael.credit) as total_credit')
            )
            ->groupBy('cc.id', 'cc.code', 'cc.name', 'a.id', 'a.code', 'a.name')
            ->orderBy('cc.code')
            ->orderBy('a.code');

        if (! empty($filters['cost_center_id'])) {
            $query->where('ael.cost_center_id', $filters['cost_center_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('ae.date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('ae.date', '<=', $filters['date_to']);
        }

        $rows = $query->get();

        return [
            'filters'      => $filters,
            'cost_centers' => $rows->groupBy('cc_id')->map(function ($lines, $ccId) {
                $first = $lines->first();
                return [
                    'id'       => $ccId,
                    'code'     => $first->cc_code,
                    'name'     => $first->cc_name,
                    'accounts' => $lines->map(fn ($l) => [
                        'account_code'  => $l->account_code,
                        'account_name'  => $l->account_name,
                        'total_debit'   => (float) $l->total_debit,
                        'total_credit'  => (float) $l->total_credit,
                    ])->values()->toArray(),
                    'total_debit'  => (float) $lines->sum('total_debit'),
                    'total_credit' => (float) $lines->sum('total_credit'),
                ];
            })->values()->toArray(),
        ];
    }

    // ══════════════════════════════════════════════════════════
    // AUXILIAR POR TERCERO
    // ══════════════════════════════════════════════════════════

    public function thirdPartyLedger(array $filters): array
    {
        $dateFrom = $filters['date_from'];
        $dateTo   = $filters['date_to'];

        $baseSelect = fn () => DB::table('accounting_entry_lines as ael')
            ->join('accounting_entries as ae', 'ae.id', '=', 'ael.entry_id')
            ->join('accounts as a', 'a.id', '=', 'ael.account_id')
            ->where('ae.status', EntryStatus::POSTED->value)
            ->whereNotNull('ael.third_party_id')
            ->select(
                'ael.account_id',
                'a.code as account_code',
                'a.name as account_name',
                'a.nature as account_nature',
                'ael.third_party_type',
                'ael.third_party_id',
                DB::raw('SUM(ael.debit) as total_debit'),
                DB::raw('SUM(ael.credit) as total_credit')
            )
            ->groupBy('ael.account_id', 'a.code', 'a.name', 'a.nature', 'ael.third_party_type', 'ael.third_party_id')
            ->orderBy('a.code')
            ->orderBy('ael.third_party_type')
            ->orderBy('ael.third_party_id');

        $applyFilters = function ($query) use ($filters) {
            if (! empty($filters['account_id'])) {
                $query->where('ael.account_id', $filters['account_id']);
            }
            if (! empty($filters['third_party_type']) && ! empty($filters['third_party_id'])) {
                $query->where('ael.third_party_type', $filters['third_party_type'])
                      ->where('ael.third_party_id', $filters['third_party_id']);
            }
            return $query;
        };

        $openKey = fn ($r) => $r->account_id . '|' . $r->third_party_type . '|' . $r->third_party_id;

        $openingSums = $applyFilters($baseSelect())->whereDate('ae.date', '<', $dateFrom)->get()->keyBy($openKey);
        $periodSums  = $applyFilters($baseSelect())->whereDate('ae.date', '>=', $dateFrom)->whereDate('ae.date', '<=', $dateTo)->get()->keyBy($openKey);

        $allKeys = $openingSums->keys()->merge($periodSums->keys())->unique();

        // Resolver nombres de terceros (una query por tipo de modelo)
        $allRecords = $openingSums->values()->merge($periodSums->values())
            ->unique(fn ($r) => $r->third_party_type . '|' . $r->third_party_id);
        $thirdPartyNames = $this->resolveThirdPartyNames($allRecords);

        $rows        = [];
        $grandTotals = ['opening' => 0.0, 'debit' => 0.0, 'credit' => 0.0, 'net' => 0.0, 'closing' => 0.0];

        foreach ($allKeys as $key) {
            $open   = $openingSums->get($key);
            $period = $periodSums->get($key);
            $src    = $open ?? $period;

            $nature      = AccountNature::from($src->account_nature);
            $openDebit   = (float) ($open->total_debit  ?? 0);
            $openCredit  = (float) ($open->total_credit ?? 0);
            $perDebit    = (float) ($period->total_debit  ?? 0);
            $perCredit   = (float) ($period->total_credit ?? 0);

            $openingBalance = $nature->netBalance($openDebit, $openCredit);
            $net            = $nature->netBalance($perDebit, $perCredit);
            $closingBalance = $openingBalance + $net;

            $tpKey = $src->third_party_type . '|' . $src->third_party_id;

            $rows[] = [
                'account_id'       => $src->account_id,
                'account_code'     => $src->account_code,
                'account_name'     => $src->account_name,
                'account_nature'   => $src->account_nature,
                'third_party_type' => $src->third_party_type,
                'third_party_id'   => $src->third_party_id,
                'third_party_name' => $thirdPartyNames[$tpKey] ?? ($src->third_party_type . '#' . $src->third_party_id),
                'opening_balance'  => $openingBalance,
                'period_debit'     => $perDebit,
                'period_credit'    => $perCredit,
                'net'              => $net,
                'closing_balance'  => $closingBalance,
            ];

            $grandTotals['opening'] += $openingBalance;
            $grandTotals['debit']   += $perDebit;
            $grandTotals['credit']  += $perCredit;
            $grandTotals['net']     += $net;
            $grandTotals['closing'] += $closingBalance;
        }

        usort($rows, fn ($a, $b) => strcmp($a['account_code'], $b['account_code'])
            ?: strcmp($a['third_party_name'], $b['third_party_name']));

        // Agrupar por cuenta para la vista
        $grouped = [];
        foreach ($rows as $row) {
            $aid = $row['account_id'];
            if (! isset($grouped[$aid])) {
                $grouped[$aid] = [
                    'account_code' => $row['account_code'],
                    'account_name' => $row['account_name'],
                    'rows'         => [],
                    'subtotals'    => ['opening' => 0.0, 'debit' => 0.0, 'credit' => 0.0, 'net' => 0.0, 'closing' => 0.0],
                ];
            }
            $grouped[$aid]['rows'][]            = $row;
            $grouped[$aid]['subtotals']['opening']  += $row['opening_balance'];
            $grouped[$aid]['subtotals']['debit']    += $row['period_debit'];
            $grouped[$aid]['subtotals']['credit']   += $row['period_credit'];
            $grouped[$aid]['subtotals']['net']      += $row['net'];
            $grouped[$aid]['subtotals']['closing']  += $row['closing_balance'];
        }

        return [
            'filters'     => $filters,
            'accounts'    => array_values($grouped),
            'grand_totals'=> $grandTotals,
        ];
    }

    private function resolveThirdPartyNames(\Illuminate\Support\Collection $records): array
    {
        $names   = [];
        $configs = config('contable.terceros', []);

        foreach ($records->groupBy('third_party_type') as $type => $items) {
            $cfg = collect($configs)->firstWhere('model', $type);

            if (! $cfg) {
                foreach ($items as $item) {
                    $names[$type . '|' . $item->third_party_id] = $type . '#' . $item->third_party_id;
                }
                continue;
            }

            $attr     = $cfg['display_attribute'];
            $modelCls = $cfg['model'];
            $ids      = $items->pluck('third_party_id')->unique()->toArray();

            try {
                $loaded = $modelCls::whereIn('id', $ids)->get()->keyBy('id');
                foreach ($items as $item) {
                    $m = $loaded->get($item->third_party_id);
                    $names[$type . '|' . $item->third_party_id] = $m ? ($m->{$attr} ?? '—') : '—';
                }
            } catch (\Throwable) {
                foreach ($items as $item) {
                    $names[$type . '|' . $item->third_party_id] = '—';
                }
            }
        }

        return $names;
    }

    // ══════════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ══════════════════════════════════════════════════════════

    /**
     * Suma débitos y créditos agrupados por account_id.
     * Acepta filtros de rango, corte "before" y opcionalmente centro de costo.
     * Con costCenterId solo se cuentan las líneas de ese CC; los totales
     * parciales NO cuadran por partida doble (el otro lado puede estar en otro CC).
     */
    private function aggregateSums(
        ?string $from           = null,
        ?string $to             = null,
        ?string $before         = null,
        ?int    $costCenterId   = null,
        bool    $excludeClosing = false,
    ): Collection {
        $query = DB::table('accounting_entry_lines as ael')
            ->join('accounting_entries as ae', 'ae.id', '=', 'ael.entry_id')
            ->where('ae.status', EntryStatus::POSTED->value)
            ->select('ael.account_id', DB::raw('SUM(ael.debit) as total_debit'), DB::raw('SUM(ael.credit) as total_credit'))
            ->groupBy('ael.account_id');

        if ($excludeClosing) {
            $closingIds = DB::table('accounting_fiscal_years')
                ->whereNotNull('closing_entry_id')
                ->pluck('closing_entry_id')
                ->toArray();

            if (! empty($closingIds)) {
                $query->whereNotIn('ae.id', $closingIds);
            }
        }

        if ($costCenterId) {
            $query->where('ael.cost_center_id', $costCenterId);
        }

        if ($before) {
            $query->whereDate('ae.date', '<', $before);
        } else {
            if ($from) $query->whereDate('ae.date', '>=', $from);
            if ($to)   $query->whereDate('ae.date', '<=', $to);
        }

        return $query->get()->keyBy('account_id');
    }

    /**
     * Suma raw de débitos y créditos para un set de IDs de cuenta.
     * Retorna [total_debit, total_credit].
     */
    private function rawSums(array $accountIds, ?string $before = null, ?string $from = null, ?string $to = null): array
    {
        $query = DB::table('accounting_entry_lines as ael')
            ->join('accounting_entries as ae', 'ae.id', '=', 'ael.entry_id')
            ->whereIn('ael.account_id', $accountIds)
            ->where('ae.status', EntryStatus::POSTED->value);

        if ($before) {
            $query->whereDate('ae.date', '<', $before);
        } else {
            if ($from) $query->whereDate('ae.date', '>=', $from);
            if ($to)   $query->whereDate('ae.date', '<=', $to);
        }

        $row = $query->selectRaw('SUM(ael.debit) as d, SUM(ael.credit) as c')->first();

        return [(float) ($row->d ?? 0), (float) ($row->c ?? 0)];
    }

    /**
     * Construye nodos del árbol con saldos propios y consolidados recursivamente.
     */
    private function buildNodes(Collection $accounts, Collection $sums): array
    {
        $nodes = [];

        foreach ($accounts as $account) {
            $s         = $sums->get($account->id);
            $ownDebit  = (float) ($s->total_debit  ?? 0);
            $ownCredit = (float) ($s->total_credit ?? 0);

            $children   = $account->relationLoaded('children')
                ? $this->buildNodes($account->children, $sums)
                : [];

            $consDebit  = $ownDebit  + array_sum(array_column($children, 'consolidated_debit'));
            $consCredit = $ownCredit + array_sum(array_column($children, 'consolidated_credit'));

            $nodes[] = [
                'id'                 => $account->id,
                'code'               => $account->code,
                'name'               => $account->name,
                'nature'             => $account->nature->value,
                'own_debit'          => $ownDebit,
                'own_credit'         => $ownCredit,
                'consolidated_debit' => $consDebit,
                'consolidated_credit'=> $consCredit,
                'balance'            => $account->nature->netBalance($consDebit, $consCredit),
                'children'           => $children,
            ];
        }

        return $nodes;
    }

    /**
     * Convierte un saldo neto a columnas deudor/acreedor según la naturaleza.
     * Retorna [debit_col, credit_col].
     */
    private function splitBalance(float $net, AccountNature $nature): array
    {
        if ($net >= 0) {
            return $nature === AccountNature::DEBIT
                ? [$net, 0.0]
                : [0.0, $net];
        }

        return $nature === AccountNature::DEBIT
            ? [0.0, abs($net)]
            : [abs($net), 0.0];
    }
}
