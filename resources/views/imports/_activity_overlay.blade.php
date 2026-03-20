<div id="import-activity-overlay" class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/70 px-4 py-6">
    <div class="w-full max-w-xl overflow-hidden rounded-[1.75rem] border border-white/10 bg-white shadow-2xl">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Actividad del sistema</p>
            <h3 id="import-activity-title" class="mt-1 text-lg font-semibold text-[#555555]">Procesando solicitud</h3>
            <p id="import-activity-context" class="mt-1 text-xs font-medium uppercase tracking-[0.14em] text-slate-400">Importaciones MPSFP</p>
            <p id="import-activity-subtitle" class="mt-2 text-sm text-slate-500">El sistema está trabajando. Mantén esta pantalla abierta.</p>
        </div>

        <div class="space-y-5 px-5 py-5">
            <div class="rounded-2xl bg-slate-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3 text-xs font-medium text-slate-500">
                    <span id="import-activity-stage">Preparando...</span>
                    <span><span id="import-activity-percent">0.00</span>%</span>
                </div>
                <div class="mt-3 h-4 overflow-hidden rounded-full bg-white ring-1 ring-slate-200">
                    <div id="import-activity-bar" class="h-full rounded-full bg-[#E6007E] transition-all duration-300" style="width: 0%;"></div>
                </div>
                <p id="import-activity-message" class="mt-3 text-sm text-slate-600">Esperando respuesta del servidor...</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Archivo</p>
                    <p id="import-activity-file" class="mt-2 text-sm font-semibold text-slate-800">—</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Proveedor</p>
                    <p id="import-activity-supplier" class="mt-2 text-sm font-semibold text-slate-800">—</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Acción</p>
                    <p id="import-activity-action" class="mt-2 text-sm font-semibold text-slate-800">Procesando</p>
                </div>
            </div>

            <div id="import-activity-note" class="rounded-2xl border border-[#E6007E]/15 bg-[#FFF7FB] px-4 py-3 text-sm text-slate-600">
                Verás esta capa mientras haya trabajo en curso para que no parezca que la app se quedó congelada.
            </div>

            <div id="import-activity-error" class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
        </div>
    </div>
</div>
