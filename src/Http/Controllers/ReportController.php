<?php

namespace Numerar\Contable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Facades\Accounting;
use Numerar\Contable\Models\Account;
use Numerar\Contable\Models\CostCenter;

class ReportController extends Controller
{
    public function journal(Request $request)
    {
        $accounts = Account::active()->movement()->orderBy('code')->get();

        if (! $request->hasAny(['date_from', 'date_to'])) {
            return view('contable::reports.journal', compact('accounts'));
        }

        $request->validate([
            'date_from'  => ['required', 'date'],
            'date_to'    => ['required', 'date', 'after_or_equal:date_from'],
            'entry_type' => ['nullable', 'in:CI,CE,CD,CA,CC,NC'],
            'account_id' => ['nullable', 'exists:accounts,id'],
        ]);

        $data = Accounting::journal($request->only('date_from', 'date_to', 'entry_type', 'account_id'));

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('contable::reports.journal', compact('data', 'accounts'));
    }

    public function generalLedger(Request $request)
    {
        $accounts = Account::active()->movement()->orderBy('code')->get();

        if (! $request->hasAny(['date_from', 'date_to', 'account_id'])) {
            return view('contable::reports.general-ledger', compact('accounts'));
        }

        $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'date_from'  => ['required', 'date'],
            'date_to'    => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $data = Accounting::generalLedger($request->only('account_id', 'date_from', 'date_to'));

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('contable::reports.general-ledger', compact('data', 'accounts'));
    }

    public function trialBalance(Request $request)
    {
        $costCenters = CostCenter::active()->orderBy('code')->get();

        if (! $request->hasAny(['date_from', 'date_to'])) {
            return view('contable::reports.trial-balance', compact('costCenters'));
        }

        $request->validate([
            'date_from'      => ['required', 'date'],
            'date_to'        => ['required', 'date', 'after_or_equal:date_from'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'close_year'     => ['nullable', 'boolean'],
        ]);

        $data = Accounting::trialBalance($request->only('date_from', 'date_to', 'cost_center_id', 'close_year'));

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('contable::reports.trial-balance', compact('data', 'costCenters'));
    }

    public function balanceSheet(Request $request)
    {
        if (! $request->has('as_of_date')) {
            return view('contable::reports.balance-sheet');
        }

        $request->validate([
            'as_of_date' => ['required', 'date'],
        ]);

        $data = Accounting::balanceSheet($request->only('as_of_date'));

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('contable::reports.balance-sheet', compact('data'));
    }

    public function incomeStatement(Request $request)
    {
        if (! $request->hasAny(['date_from', 'date_to'])) {
            return view('contable::reports.income-statement');
        }

        $request->validate([
            'date_from' => ['required', 'date'],
            'date_to'   => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $data = Accounting::incomeStatement($request->only('date_from', 'date_to'));

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('contable::reports.income-statement', compact('data'));
    }

    public function thirdPartyLedger(Request $request)
    {
        $accounts = Account::active()->movement()->orderBy('code')->get();
        $terceros = $this->buildTerceroOptions();

        if (! $request->hasAny(['date_from', 'date_to'])) {
            return view('contable::reports.third-party-ledger', compact('accounts', 'terceros'));
        }

        $request->validate([
            'date_from'       => ['required', 'date'],
            'date_to'         => ['required', 'date', 'after_or_equal:date_from'],
            'account_id'      => ['nullable', 'exists:accounts,id'],
            'third_party_ref' => ['nullable', 'string'],
        ]);

        $filters = $request->only('date_from', 'date_to', 'account_id');

        if ($ref = $request->input('third_party_ref')) {
            $sep = strrpos($ref, '|');
            $filters['third_party_type'] = substr($ref, 0, $sep);
            $filters['third_party_id']   = (int) substr($ref, $sep + 1);
        }

        $data = Accounting::thirdPartyLedger($filters);

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('contable::reports.third-party-ledger', compact('data', 'accounts', 'terceros'));
    }

    public function costCenter(Request $request)
    {
        $costCenters = CostCenter::active()->orderBy('code')->get();

        if (! $request->hasAny(['date_from', 'date_to', 'cost_center_id'])) {
            return view('contable::reports.cost-center', compact('costCenters'));
        }

        $request->validate([
            'date_from'      => ['nullable', 'date'],
            'date_to'        => ['nullable', 'date', 'after_or_equal:date_from'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
        ]);

        $data = Accounting::costCenterReport($request->only('date_from', 'date_to', 'cost_center_id'));

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return view('contable::reports.cost-center', compact('data', 'costCenters'));
    }

    private function buildTerceroOptions(): array
    {
        $groups = [];

        foreach (config('contable.terceros', []) as $config) {
            $modelClass  = $config['model'];
            $label       = $config['label'];
            $displayAttr = $config['display_attribute'];
            $searchAttrs = $config['search_attributes'] ?? [$displayAttr];

            $records = $modelClass::orderBy($searchAttrs[0])->get();

            $options = $records->map(fn ($record) => [
                'ref'   => $modelClass . '|' . $record->id,
                'label' => $record->{$displayAttr} ?? "#{$record->id}",
            ])->all();

            $groups[] = ['label' => $label, 'options' => $options];
        }

        return $groups;
    }
}
