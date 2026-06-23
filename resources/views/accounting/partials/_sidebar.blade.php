@php
$is = fn(string $pattern) => request()->routeIs($pattern);

$item = function(string $route, string $pattern) use ($is): string {
    $base   = 'group flex items-center gap-3 px-4 py-2.5 text-[13px] font-medium transition-all duration-100 border-l-[3px]';
    $on     = 'bg-sage/20 border-sage text-white';
    $off    = 'border-transparent text-mint/70 hover:bg-white/5 hover:text-white hover:border-mint/40';
    return $base . ' ' . ($is($pattern) ? $on : $off);
};
@endphp

{{-- ── Marca ──────────────────────────────────────────────────── --}}
<div class="px-5 py-4 border-b border-ink shrink-0 bg-ink/20">
    <div class="flex items-center gap-3">
        {{-- Ícono libro mayor --}}
        <div class="w-9 h-9 bg-sage flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M2 6.5A2.5 2.5 0 0 1 4.5 4h14A2.5 2.5 0 0 1 21 6.5v11A2.5 2.5 0 0 1 18.5 20h-14A2.5 2.5 0 0 1 2 17.5v-11Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 4v16M12 8h5M12 12h5M12 16h3"/>
            </svg>
        </div>
        <div>
            <p class="text-white font-extrabold text-sm tracking-widest uppercase leading-none">Numerar</p>
            <p class="text-mint text-[10px] tracking-[0.2em] uppercase mt-0.5">Accounting</p>
        </div>
    </div>
</div>

{{-- ── Usuario ─────────────────────────────────────────────────── --}}
@auth
<div class="px-4 py-3 border-b border-ink/40 shrink-0 flex items-center gap-3">
    <div class="w-8 h-8 bg-sage/30 border border-sage/50 flex items-center justify-center text-mint text-xs font-bold uppercase shrink-0">
        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
    </div>
    <div class="min-w-0">
        <p class="text-white text-[12px] font-semibold truncate">{{ auth()->user()->name ?? 'Usuario' }}</p>
        <p class="text-mint/50 text-[10px] truncate">{{ auth()->user()->email ?? '' }}</p>
    </div>
</div>
@endauth

{{-- ── Navegación ──────────────────────────────────────────────── --}}
<nav class="flex-1 overflow-y-auto py-2">

    {{-- Dashboard --}}
    <a href="{{ route('contable.dashboard') }}" class="{{ $item('accounting.dashboard', 'accounting.dashboard') }}">
        {{-- ícono: cuadrantes / overview --}}
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="3" y="3" width="7" height="7" rx="1" stroke-linecap="round"/>
            <rect x="14" y="3" width="7" height="7" rx="1" stroke-linecap="round"/>
            <rect x="3" y="14" width="7" height="7" rx="1" stroke-linecap="round"/>
            <rect x="14" y="14" width="7" height="7" rx="1" stroke-linecap="round"/>
        </svg>
        Inicio
    </a>

    {{-- ── Catálogo ── --}}
    <p class="px-4 pt-5 pb-1.5 text-[9px] font-bold uppercase tracking-[0.18em] text-mint/40">Catálogo</p>

    <a href="{{ route('contable.account-classes.index') }}" class="{{ $item('accounting.account-classes.index', 'accounting.account-classes.*') }}">
        {{-- ícono: etiquetas/clases --}}
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L9.568 3Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
        </svg>
        Clases Contables
    </a>

    <a href="{{ route('contable.accounts.index') }}" class="{{ $item('accounting.accounts.index', 'accounting.accounts.*') }}">
        {{-- ícono: árbol jerárquico --}}
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 7a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7ZM15 4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2V4ZM15 17a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-2Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h3a3 3 0 0 1 3 3v2M12 18h3"/>
        </svg>
        Plan de Cuentas
    </a>

    <a href="{{ route('contable.cost-centers.index') }}" class="{{ $item('accounting.cost-centers.index', 'accounting.cost-centers.*') }}">
        {{-- ícono: edificio --}}
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
        </svg>
        Centros de Costo
    </a>

    <a href="{{ route('contable.terceros.index') }}" class="{{ $item('accounting.terceros.index', 'accounting.terceros.*') }}">
        {{-- ícono: personas --}}
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
        </svg>
        Terceros
    </a>

    {{-- ── Operaciones ── --}}
    <p class="px-4 pt-5 pb-1.5 text-[9px] font-bold uppercase tracking-[0.18em] text-mint/40">Operaciones</p>

    <a href="{{ route('contable.periods.index') }}" class="{{ $item('accounting.periods.index', 'accounting.periods.*') }}">
        {{-- ícono: calendario --}}
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
        </svg>
        Periodos Contables
    </a>

    <a href="{{ route('contable.entries.index') }}" class="{{ $item('accounting.entries.index', 'accounting.entries.*') }}">
        {{-- ícono: recibo / comprobante --}}
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
        </svg>
        Comprobantes
    </a>

    {{-- ── Configuración ── --}}
    <p class="px-4 pt-5 pb-1.5 text-[9px] font-bold uppercase tracking-[0.18em] text-mint/40">Configuración</p>

    <a href="{{ route('contable.entry-types.index') }}" class="{{ $item('accounting.entry-types.index', 'accounting.entry-types.*') }}">
        {{-- ícono: ajustes --}}
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
        </svg>
        Tipos de Comprobante
    </a>

    {{-- ── Reportes ── --}}
    <p class="px-4 pt-5 pb-1.5 text-[9px] font-bold uppercase tracking-[0.18em] text-mint/40">Reportes</p>

    <a href="{{ route('contable.reports.journal') }}" class="{{ $item('accounting.reports.journal', 'accounting.reports.journal') }}">
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
        </svg>
        Libro Diario
    </a>

    <a href="{{ route('contable.reports.general-ledger') }}" class="{{ $item('accounting.reports.general-ledger', 'accounting.reports.general-ledger') }}">
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75.125V5.625m0 0A2.25 2.25 0 0 1 5.25 3.375h13.5A2.25 2.25 0 0 1 21 5.625v12.75m-9-10.875h3.75m-3.75 3.375h3.75M6 10.5h3.75m-3.75 3.375h3.75"/>
        </svg>
        Libro Mayor
    </a>

    <a href="{{ route('contable.reports.trial-balance') }}" class="{{ $item('accounting.reports.trial-balance', 'accounting.reports.trial-balance') }}">
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.97Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 5.49Z"/>
        </svg>
        Balance de Prueba
    </a>

    <a href="{{ route('contable.reports.balance-sheet') }}" class="{{ $item('accounting.reports.balance-sheet', 'accounting.reports.balance-sheet') }}">
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/>
        </svg>
        Est. Situación Financiera
    </a>

    <a href="{{ route('contable.reports.income-statement') }}" class="{{ $item('accounting.reports.income-statement', 'accounting.reports.income-statement') }}">
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/>
        </svg>
        Estado de Resultados
    </a>

    <a href="{{ route('contable.reports.cost-center') }}" class="{{ $item('accounting.reports.cost-center', 'accounting.reports.cost-center') }}">
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z"/>
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z"/>
        </svg>
        Por Centro de Costo
    </a>

    <a href="{{ route('contable.reports.third-party-ledger') }}" class="{{ $item('accounting.reports.third-party-ledger', 'accounting.reports.third-party-ledger') }}">
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
        </svg>
        Auxiliar por Tercero
    </a>

</nav>

{{-- ── Pie ─────────────────────────────────────────────────────── --}}
<div class="px-4 py-3 border-t border-ink shrink-0 bg-ink/20">
    <p class="text-[9px] font-mono text-mint/30 uppercase tracking-widest">
        Numerar Contable · v1.0
    </p>
</div>
