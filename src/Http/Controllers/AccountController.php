<?php

namespace Numerar\Contable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Enums\AccountType;
use Numerar\Contable\Facades\Accounting;
use Numerar\Contable\Http\Requests\StoreAccountRequest;
use Numerar\Contable\Http\Resources\AccountResource;
use Numerar\Contable\Models\Account;
use Numerar\Contable\Models\AccountClass;
use Numerar\Contable\Models\AccountingEntryLine;

class AccountController extends Controller
{
    public function index()
    {
        $roots = Account::with('class')
            ->withCount('children')
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();

        $classes    = AccountClass::active()->orderBy('code')->get();
        $totalCount = Account::count();

        return view('contable::accounts.index', compact('roots', 'classes', 'totalCount'));
    }

    public function children(Account $account)
    {
        $children = Account::with('class')
            ->withCount('children')
            ->where('parent_id', $account->id)
            ->orderBy('code')
            ->get();

        return response()->json($children->map(fn ($a) => [
            'id'           => $a->id,
            'code'         => $a->code,
            'name'         => $a->name,
            'nature'       => $a->nature->value,
            'account_type' => $a->account_type->value,
            'active'       => $a->active,
            'has_children' => $a->children_count > 0,
            'class_name'   => $a->class?->name,
            'edit_url'    => route('contable.accounts.edit', $a),
            'delete_url'  => route('contable.accounts.delete', $a),
        ]));
    }

    public function tree()
    {
        $tree = Accounting::accountTree();
        return response()->json(['data' => AccountResource::collection($tree)]);
    }

    public function flat()
    {
        $flat = Accounting::accountFlat(
            classId:      request()->integer('class_id') ?: null,
            onlyMovement: request()->boolean('only_movement')
        );
        return response()->json(['data' => $flat]);
    }

    public function create()
    {
        $classes = AccountClass::active()->orderBy('code')->get();
        $parents = Accounting::accountFlat();

        return view('contable::accounts.create', compact('classes', 'parents'));
    }

    public function store(StoreAccountRequest $request)
    {
        $account = Accounting::createAccount($request->validated());

        if ($request->expectsJson()) {
            return AccountResource::make($account->load('class'))
                ->response()->setStatusCode(201);
        }

        return redirect()->route('contable.accounts.index')
            ->with('success', "Cuenta '{$account->full_name}' creada exitosamente.");
    }

    public function edit(Account $account)
    {
        $classes = AccountClass::active()->orderBy('code')->get();
        $parents = Accounting::accountFlat();

        return view('contable::accounts.edit', compact('account', 'classes', 'parents'));
    }

    public function update(StoreAccountRequest $request, Account $account)
    {
        $account = Accounting::updateAccount($account, $request->validated());

        if ($request->expectsJson()) {
            return AccountResource::make($account->load('class'));
        }

        return redirect()->route('contable.accounts.index')
            ->with('success', "Cuenta '{$account->full_name}' actualizada.");
    }

    public function toggle(Account $account)
    {
        $account = Accounting::toggleAccount($account);
        $label   = $account->active ? 'activada' : 'inactivada';

        if (request()->expectsJson()) {
            return AccountResource::make($account);
        }

        return back()->with('success', "Cuenta '{$account->full_name}' {$label}.");
    }

    public function showDelete(Account $account)
    {
        if ($account->children()->exists()) {
            return redirect()->route('contable.accounts.index')
                ->with('error', "No se puede eliminar '{$account->full_name}': tiene subcuentas vinculadas. Elimínalas primero.");
        }

        $hasMovements = AccountingEntryLine::where('account_id', $account->id)->exists();

        $hasClosedPeriodMovements = false;
        $transferTargets          = [];

        if ($hasMovements) {
            $hasClosedPeriodMovements = AccountingEntryLine::query()
                ->where('accounting_entry_lines.account_id', $account->id)
                ->join('accounting_entries', 'accounting_entries.id', '=', 'accounting_entry_lines.entry_id')
                ->join('accounting_periods', 'accounting_periods.id', '=', 'accounting_entries.accounting_period_id')
                ->whereIn('accounting_periods.status', ['CLOSED', 'LOCKED'])
                ->exists();

            $all             = Accounting::accountFlat(onlyMovement: true);
            $excludeIds      = array_merge([$account->id], $account->descendantIds());
            $transferTargets = array_values(array_filter($all, fn ($t) => ! in_array($t['id'], $excludeIds)));
        }

        return view('contable::accounts.delete', compact(
            'account',
            'hasMovements',
            'hasClosedPeriodMovements',
            'transferTargets',
        ));
    }

    public function destroy(Account $account, Request $request)
    {
        if ($account->children()->exists()) {
            return redirect()->route('contable.accounts.index')
                ->with('error', "No se puede eliminar '{$account->full_name}': tiene subcuentas vinculadas.");
        }

        $hasMovements = AccountingEntryLine::where('account_id', $account->id)->exists();

        if ($hasMovements) {
            $request->validate([
                'target_account_id' => ['required', 'integer', 'exists:accounts,id'],
            ], [
                'target_account_id.required' => 'Debe seleccionar una cuenta destino para el traslado.',
                'target_account_id.exists'   => 'La cuenta destino seleccionada no existe.',
            ]);

            $target = Account::findOrFail($request->target_account_id);

            if ($target->id === $account->id) {
                return back()->withErrors(['target_account_id' => 'La cuenta destino no puede ser la misma cuenta a eliminar.']);
            }

            if ($target->account_type !== AccountType::MOVIMIENTO) {
                return back()->withErrors(['target_account_id' => 'La cuenta destino debe ser de tipo Movimiento.']);
            }

            AccountingEntryLine::where('account_id', $account->id)
                ->update(['account_id' => $target->id]);
        }

        $name = $account->full_name;
        $account->delete();

        return redirect()->route('contable.accounts.index')
            ->with('success', "Cuenta '{$name}' eliminada exitosamente.");
    }
}
