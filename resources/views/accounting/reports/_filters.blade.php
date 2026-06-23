<form method="GET" class="bg-white border border-ink/10 px-5 py-4 mb-5">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Desde</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                       focus:outline-none focus:border-sage">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Hasta</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                       focus:outline-none focus:border-sage">
        </div>
        @if(isset($showCostCenter) && $showCostCenter)
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Centro de Costo</label>
            <select name="cost_center_id"
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                       focus:outline-none focus:border-sage">
                <option value="">Todos</option>
                @foreach($costCenters ?? [] as $cc)
                    <option value="{{ $cc->id }}" {{ request('cost_center_id') == $cc->id ? 'selected' : '' }}>
                        [{{ $cc->code }}] {{ $cc->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
        <div>
            <button type="submit"
                class="w-full px-4 py-2 bg-sage text-white text-[11px] font-bold uppercase tracking-wide
                       hover:bg-forest transition-colors">
                Generar Reporte
            </button>
        </div>
    </div>
</form>
