<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Contabilidad') — Numerar Contable</title>
    <link rel="stylesheet" href="{{ asset('vendor/accounting/app.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        html { background-color: #f0f5f2; }

        #kk-bar {
            position: fixed; top: 0; left: 0; z-index: 99999;
            height: 3px; width: 0; opacity: 0;
            background: linear-gradient(90deg, #5d8a6e, #a3c4ae);
            box-shadow: 0 0 10px #5d8a6e99;
            pointer-events: none;
        }
    </style>
</head>
<body class="h-full bg-cream font-sans antialiased" x-data="{ nav: false }">

{{-- Barra de progreso de navegación --}}
<div id="kk-bar"></div>
<script>
(function () {
    const bar = document.getElementById('kk-bar');
    let tid;

    function start() {
        clearTimeout(tid);
        bar.style.transition = 'none';
        bar.style.width = '0%';
        bar.style.opacity = '1';
        requestAnimationFrame(() => {
            bar.style.transition = 'width 6s cubic-bezier(0.03, 0.9, 0.1, 1)';
            bar.style.width = '85%';
        });
    }

    function finish() {
        bar.style.transition = 'width 0.2s ease';
        bar.style.width = '100%';
        tid = setTimeout(() => {
            bar.style.transition = 'opacity 0.3s ease';
            bar.style.opacity = '0';
        }, 200);
    }

    /* Iniciar en clic de enlace interno */
    document.addEventListener('click', function (e) {
        const a = e.target.closest('a[href]');
        if (!a || a.target === '_blank') return;
        try {
            const url = new URL(a.href);
            if (url.origin !== location.origin || url.hash) return;
        } catch { return; }
        start();
    });

    /* Iniciar en submit de formulario */
    document.addEventListener('submit', start);

    /* Completar al cargar la nueva página */
    window.addEventListener('pageshow', finish);
})();
</script>

{{-- Overlay móvil --}}
<div x-show="nav" x-cloak
     x-transition:enter="transition-opacity ease-out duration-150"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-100"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @click="nav = false"
     class="fixed inset-0 z-20 bg-ink/50 lg:hidden">
</div>

<div class="flex h-screen overflow-hidden">

    {{-- ── Sidebar ──────────────────────────────────────────────── --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-30 flex flex-col w-64 bg-forest border-r-2 border-ink
                  transform transition-transform duration-200 ease-out
                  lg:static lg:translate-x-0"
           :class="nav ? 'translate-x-0' : '-translate-x-full'"
           x-cloak>

        @include('contable::partials._sidebar')

    </aside>

    {{-- ── Área derecha ─────────────────────────────────────────── --}}
    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

        {{-- Topbar --}}
        <header class="flex items-center justify-between h-[68px] px-4 lg:px-6
                        bg-white border-b-2 border-ink shrink-0">
            {{-- Izquierda --}}
            <div class="flex items-center gap-3 min-w-0">
                <button @click="nav = !nav"
                        class="lg:hidden w-8 h-8 flex items-center justify-center
                               text-ink border border-ink/30 hover:border-sage hover:text-sage
                               transition-colors"
                        aria-label="Menú">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h10"/>
                    </svg>
                </button>

                <div class="min-w-0">
                    <h1 class="text-[13px] font-bold text-ink uppercase tracking-wider truncate">
                        @yield('page-title', 'Dashboard')
                    </h1>
                    @hasSection('breadcrumb')
                        <p class="text-[11px] text-ink/40 truncate">
                            Contabilidad / @yield('breadcrumb')
                        </p>
                    @endif
                </div>
            </div>

            {{-- Derecha --}}
            <div class="flex items-center gap-3 shrink-0">
                @yield('header-actions')
                @auth
                    <div class="flex items-center gap-2 pl-3 border-l border-ink/10">
                        <div class="w-7 h-7 rounded-sm bg-sage flex items-center justify-center
                                    text-white text-xs font-bold uppercase">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <span class="hidden md:block text-[12px] text-ink/70 max-w-[120px] truncate">
                            {{ auth()->user()->name ?? '' }}
                        </span>
                    </div>
                @endauth
            </div>
        </header>

        {{-- Flash --}}
        @include('contable::partials._flash')

        {{-- Contenido --}}
        <main class="flex-1 overflow-y-auto p-5 lg:p-7 bg-cream">
            @yield('content')
        </main>
    </div>

</div>

@stack('modals')
@stack('scripts')
</body>
</html>
