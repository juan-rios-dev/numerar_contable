@extends('contable::layouts.app')

@section('title', 'Eliminar Cuenta')
@section('page-title', 'Eliminar Cuenta')
@section('breadcrumb')
    <a href="{{ route('contable.accounts.index') }}" class="hover:text-indigo-600">Plan de Cuentas</a>
    <span>/</span> Eliminar
@endsection

@section('content')
<div class="max-w-2xl">

    {{-- Información de la cuenta --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-5 mb-5">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Cuenta a eliminar</p>
        <div class="flex items-center gap-3">
            @if($account->code)
            <code class="text-sm bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg font-mono font-semibold">{{ $account->code }}</code>
            @endif
            <span class="text-base font-semibold text-slate-800">{{ $account->name }}</span>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full
                {{ $account->nature->value === 'DEBIT' ? 'text-blue-700 bg-blue-100' : 'text-emerald-700 bg-emerald-100' }}">
                {{ $account->nature->value === 'DEBIT' ? 'Débito' : 'Crédito' }}
            </span>
        </div>
    </div>

    @if(! $hasMovements)
    {{-- Sin movimientos: confirmación simple --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm divide-y divide-slate-100">
        <div class="px-6 py-5">
            <div class="flex items-start gap-3 text-slate-700">
                <svg class="w-5 h-5 shrink-0 mt-0.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-slate-800">Esta cuenta no tiene movimientos registrados</p>
                    <p class="text-sm text-slate-500 mt-0.5">Se puede eliminar de forma definitiva sin ningún impacto en el historial contable.</p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 bg-slate-50 flex items-center justify-between rounded-b-xl">
            <a href="{{ route('contable.accounts.index') }}"
                class="text-sm font-medium text-slate-600 hover:text-slate-800 transition-colors">
                ← Cancelar
            </a>
            <form method="POST" action="{{ route('contable.accounts.destroy', $account) }}">
                @csrf @method('DELETE')
                <button type="submit"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors shadow-sm">
                    Eliminar cuenta
                </button>
            </form>
        </div>
    </div>

    @else
    {{-- Con movimientos: formulario de traslado --}}
    <form method="POST" action="{{ route('contable.accounts.destroy', $account) }}"
          x-data="{ targetId: '' }">
        @csrf @method('DELETE')

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm divide-y divide-slate-100">

            {{-- Explicación --}}
            <div class="px-6 py-5 space-y-3">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0 mt-0.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Esta cuenta tiene transacciones registradas</p>
                        <p class="text-sm text-slate-500 mt-0.5">
                            Elige la cuenta que recibirá las transacciones asociadas de la cuenta
                            <strong class="text-slate-700">"{{ $account->name }}"</strong>
                            que vas a eliminar.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Aviso periodos cerrados --}}
            @if($hasClosedPeriodMovements)
            <div class="px-6 py-4 bg-amber-50">
                <div class="flex items-start gap-3 text-amber-800">
                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide mb-1">Ten en cuenta antes de continuar</p>
                        <p class="text-xs leading-relaxed">
                            La cuenta tiene transacciones en <strong>periodos cerrados</strong>.
                            Todos los movimientos, incluyendo los de periodos cerrados, serán trasladados a la cuenta que selecciones.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Selector de cuenta destino --}}
            <div class="px-6 py-5 space-y-2">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                    Cuenta destino <span class="text-red-500">*</span>
                    <span class="font-normal text-slate-400 ml-1">(solo cuentas de movimiento)</span>
                </label>
                <select name="target_account_id" required x-model="targetId"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 font-mono">
                    <option value="">Seleccionar cuenta destino...</option>
                    @foreach($transferTargets as $target)
                    <option value="{{ $target['id'] }}">{{ $target['label'] }}</option>
                    @endforeach
                </select>
                @error('target_account_id')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
                @if(empty($transferTargets))
                <p class="text-xs text-amber-600 mt-1">No hay cuentas de movimiento disponibles para el traslado.</p>
                @endif
            </div>

            {{-- Acciones --}}
            <div class="px-6 py-4 bg-slate-50 flex items-center justify-between rounded-b-xl">
                <a href="{{ route('contable.accounts.index') }}"
                    class="text-sm font-medium text-slate-600 hover:text-slate-800 transition-colors">
                    ← Cancelar
                </a>
                <button type="submit"
                    :disabled="!targetId"
                    :class="targetId ? 'bg-red-600 hover:bg-red-700 cursor-pointer' : 'bg-red-300 cursor-not-allowed'"
                    class="px-5 py-2.5 text-sm font-medium text-white rounded-lg transition-colors shadow-sm">
                    Trasladar y eliminar cuenta
                </button>
            </div>
        </div>
    </form>
    @endif

</div>
@endsection
