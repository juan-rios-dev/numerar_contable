@extends('contable::layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('header-actions')
    <a href="{{ route('contable.entries.create') }}"
       class="flex items-center gap-2 px-3 py-1.5 bg-sage text-white text-xs font-semibold
              uppercase tracking-wide hover:bg-forest transition-colors">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo Comprobante
    </a>
@endsection

@section('content')
@php
    use Numerar\Contable\Models\{Account, AccountingEntry, AccountingPeriod, CostCenter};
    use Numerar\Contable\Services\ReportService;

    $currentPeriod    = AccountingPeriod::open()->orderByDesc('year')->orderByDesc('month')->first();
    $openPeriods      = AccountingPeriod::open()->count();
    $totalAccounts    = Account::active()->count();
    $totalCostCenters = CostCenter::active()->count();

    $postedThisMonth = AccountingEntry::posted()
        ->when($currentPeriod, fn($q) => $q->where('accounting_period_id', $currentPeriod->id))
        ->count();

    $yearStart = now()->startOfYear()->format('Y-m-d');
    $yearEnd   = now()->format('Y-m-d');

    $svc = app(ReportService::class);

    $income    = $svc->incomeStatement(['date_from' => $yearStart, 'date_to' => $yearEnd]);
    $netProfit = $income['net_profit'] ?? 0;

    $trial         = $svc->trialBalance(['date_from' => $yearStart, 'date_to' => $yearEnd, 'close_year' => false, 'cost_center_id' => null]);
    $trialSections = $trial['sections'] ?? [];
    $trialTotals   = $trial['totals']   ?? [];

    $lastEntries = AccountingEntry::with('period')
        ->orderByDesc('date')->orderByDesc('id')
        ->limit(10)->get();
@endphp

{{-- ── KPIs ──────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">

    <div class="bg-white border border-ink/10 border-l-[3px] border-l-sage p-4">
        <p class="text-[10px] font-bold text-ink/40 uppercase tracking-widest mb-2">Periodo Activo</p>
        <p class="text-xl font-extrabold text-ink leading-none">
            {{ $currentPeriod ? $currentPeriod->name : '—' }}
        </p>
        <p class="text-[11px] text-ink/40 mt-1.5">{{ $openPeriods }} periodo(s) abierto(s)</p>
    </div>

    <div class="bg-white border border-ink/10 border-l-[3px] border-l-sage p-4">
        <p class="text-[10px] font-bold text-ink/40 uppercase tracking-widest mb-2">Contabilizados</p>
        <p class="text-xl font-extrabold text-ink leading-none">{{ number_format($postedThisMonth) }}</p>
        <p class="text-[11px] text-ink/40 mt-1.5">en el periodo actual</p>
    </div>

    {{-- Utilidad / Pérdida del año --}}
    @php $esUtilidad = $netProfit >= 0; @endphp
    <div class="bg-white border border-ink/10 border-l-[3px] {{ $esUtilidad ? 'border-l-sage' : 'border-l-red-400' }} p-4">
        <p class="text-[10px] font-bold text-ink/40 uppercase tracking-widest mb-2">
            {{ $esUtilidad ? 'Utilidad del Año' : 'Pérdida del Año' }}
        </p>
        <p class="text-xl font-extrabold leading-none {{ $esUtilidad ? 'text-sage' : 'text-red-500' }}">
            ${{ number_format(abs($netProfit), 0, ',', '.') }}
        </p>
        <p class="text-[11px] text-ink/40 mt-1.5">{{ now()->year }} · acumulado a hoy</p>
    </div>

    <div class="bg-white border border-ink/10 border-l-[3px] border-l-sage p-4">
        <p class="text-[10px] font-bold text-ink/40 uppercase tracking-widest mb-2">Plan de Cuentas</p>
        <p class="text-xl font-extrabold text-ink leading-none">{{ number_format($totalAccounts) }}</p>
        <p class="text-[11px] text-ink/40 mt-1.5">{{ $totalCostCenters }} centro(s) de costo</p>
    </div>

</div>

{{-- ── Fila principal ─────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 xl:grid-cols-5 gap-4 mb-4">

    {{-- Últimos comprobantes (3/5) --}}
    <div class="xl:col-span-3 bg-white border border-ink/10">
        <div class="flex items-center justify-between px-4 py-3 border-b border-ink/10 bg-forest">
            <p class="text-xs font-bold text-white uppercase tracking-widest">Últimos Comprobantes</p>
            <a href="{{ route('contable.entries.index') }}"
               class="text-[10px] text-mint/70 hover:text-mint uppercase tracking-wider transition-colors">
                Ver todos →
            </a>
        </div>

        @if($lastEntries->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-ink/25">
                <svg class="w-10 h-10 mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
                <p class="text-sm">No hay comprobantes registrados</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead>
                        <tr class="bg-ink/5 border-b border-ink/10">
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider">#</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider">Tipo</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider">Fecha</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider">Descripción</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink/5">
                        @foreach($lastEntries as $entry)
                        @php
                            $status   = $entry->status->value ?? $entry->status;
                            $isPosted = $status === 'POSTED';
                            $isVoided = $status === 'VOIDED';
                        @endphp
                        <tr class="hover:bg-cream transition-colors">
                            <td class="px-4 py-2.5">
                                <a href="{{ route('contable.entries.show', $entry) }}"
                                   class="font-mono font-semibold text-forest hover:text-sage transition-colors">
                                    {{ $entry->entry_number ?? '—' }}
                                </a>
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="px-2 py-0.5 text-[10px] font-bold bg-sage/10 text-sage border border-sage/20">
                                    {{ $entry->entry_type ?? '—' }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 font-mono text-ink/50 whitespace-nowrap">
                                {{ $entry->date?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-ink/60 max-w-[180px] truncate">
                                {{ $entry->description ?? $entry->notes ?? '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                @if($isPosted)
                                    <span class="px-2 py-0.5 text-[10px] font-bold bg-sage/10 text-sage border border-sage/20">Contabilizado</span>
                                @elseif($isVoided)
                                    <span class="px-2 py-0.5 text-[10px] font-bold bg-red-50 text-red-500 border border-red-200">Anulado</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-200">Borrador</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Balance de Prueba resumido (2/5) --}}
    <div class="xl:col-span-2 bg-white border border-ink/10">
        <div class="flex items-center justify-between px-4 py-3 border-b border-ink/10 bg-forest">
            <p class="text-xs font-bold text-white uppercase tracking-widest">Balance de Prueba</p>
            <span class="text-[10px] text-mint/50 uppercase tracking-wider">{{ now()->year }}</span>
        </div>

        @if(empty($trialSections))
            <div class="flex items-center justify-center py-12 text-ink/25 text-sm">
                Sin movimientos en {{ now()->year }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-[12px]">
                    <thead>
                        <tr class="bg-ink/5 border-b border-ink/10">
                            <th class="px-3 py-2 text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider">Cl.</th>
                            <th class="px-2 py-2 text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider">Nombre</th>
                            <th class="px-2 py-2 text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider">S.F. Db</th>
                            <th class="px-3 py-2 text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider">S.F. Cr</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink/5">
                        @foreach($trialSections as $section)
                        @php
                            $sfDb = $section['totals']['closing_debit']  ?? 0;
                            $sfCr = $section['totals']['closing_credit'] ?? 0;
                        @endphp
                        <tr class="hover:bg-cream transition-colors">
                            <td class="px-3 py-2 font-mono font-bold text-forest">
                                {{ $section['class_code'] }}
                            </td>
                            <td class="px-2 py-2 text-ink/60 truncate max-w-[90px]">
                                {{ $section['class_name'] }}
                            </td>
                            <td class="px-2 py-2 text-right font-mono text-ink/70">
                                {{ $sfDb > 0 ? '$'.number_format($sfDb, 0, ',', '.') : '' }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-ink/70">
                                {{ $sfCr > 0 ? '$'.number_format($sfCr, 0, ',', '.') : '' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-ink/20 bg-ink/5">
                            <td colspan="2" class="px-3 py-2 text-[10px] font-bold text-ink/40 uppercase tracking-wider">
                                Totales
                            </td>
                            <td class="px-2 py-2 text-right font-mono font-bold text-ink text-[11px]">
                                ${{ number_format($trialTotals['closing_debit'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono font-bold text-ink text-[11px]">
                                ${{ number_format($trialTotals['closing_credit'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        @php $balanced = abs(($trialTotals['closing_debit'] ?? 0) - ($trialTotals['closing_credit'] ?? 0)) < 0.01; @endphp
                        <tr>
                            <td colspan="4" class="px-3 py-1.5 text-center text-[10px] font-semibold
                                {{ $balanced ? 'bg-sage/5 text-sage' : 'bg-red-50 text-red-500' }}">
                                {{ $balanced ? '✓ Cuadrado' : '⚠ Descuadrado — revisar asientos' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="px-4 py-2.5 border-t border-ink/5">
                <a href="{{ route('contable.reports.trial-balance', ['date_from' => $yearStart, 'date_to' => $yearEnd]) }}"
                   class="text-[10px] text-sage hover:text-forest font-semibold uppercase tracking-wider transition-colors">
                    Ver reporte completo →
                </a>
            </div>
        @endif
    </div>

</div>

{{-- ── Acciones rápidas ────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

    <a href="{{ route('contable.entries.create') }}"
       class="flex items-center gap-3 p-3 bg-white border border-ink/10 hover:border-sage hover:bg-sage/5 transition-colors group">
        <div class="w-8 h-8 bg-forest flex items-center justify-center shrink-0 group-hover:bg-sage transition-colors">
            <svg class="w-4 h-4 text-mint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
        </div>
        <div>
            <p class="text-[11px] font-bold text-ink uppercase tracking-wide">Nuevo Comprobante</p>
            <p class="text-[10px] text-ink/40">Registrar movimiento</p>
        </div>
    </a>

    <a href="{{ route('contable.reports.trial-balance') }}"
       class="flex items-center gap-3 p-3 bg-white border border-ink/10 hover:border-sage hover:bg-sage/5 transition-colors group">
        <div class="w-8 h-8 bg-forest flex items-center justify-center shrink-0 group-hover:bg-sage transition-colors">
            <svg class="w-4 h-4 text-mint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.97Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 5.49Z"/>
            </svg>
        </div>
        <div>
            <p class="text-[11px] font-bold text-ink uppercase tracking-wide">Balance de Prueba</p>
            <p class="text-[10px] text-ink/40">Verificar equilibrio</p>
        </div>
    </a>

    <a href="{{ route('contable.reports.income-statement') }}"
       class="flex items-center gap-3 p-3 bg-white border border-ink/10 hover:border-sage hover:bg-sage/5 transition-colors group">
        <div class="w-8 h-8 bg-forest flex items-center justify-center shrink-0 group-hover:bg-sage transition-colors">
            <svg class="w-4 h-4 text-mint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/>
            </svg>
        </div>
        <div>
            <p class="text-[11px] font-bold text-ink uppercase tracking-wide">Estado de Resultados</p>
            <p class="text-[10px] text-ink/40">Ver utilidad / pérdida</p>
        </div>
    </a>

    <a href="{{ route('contable.periods.index') }}"
       class="flex items-center gap-3 p-3 bg-white border border-ink/10 hover:border-sage hover:bg-sage/5 transition-colors group">
        <div class="w-8 h-8 bg-forest flex items-center justify-center shrink-0 group-hover:bg-sage transition-colors">
            <svg class="w-4 h-4 text-mint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
            </svg>
        </div>
        <div>
            <p class="text-[11px] font-bold text-ink uppercase tracking-wide">Periodos</p>
            <p class="text-[10px] text-ink/40">Gestionar periodos</p>
        </div>
    </a>

</div>
@endsection
