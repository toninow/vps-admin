@extends('layouts.app')

@section('title', 'Nueva importación')
@section('page_title', 'Nueva importación')

@section('content')
<div class="space-y-4">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        @include('projects.mpsfp._context', [
            'project' => $mpsfpProject,
            'label' => 'Importaciones',
            'title' => 'MPSFP / Nueva importación',
            'subtitle' => 'Sube un CSV, XLSX o XML del proveedor y continúa después al preview, al mapeo y al procesamiento completo del catálogo.',
        ])
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_360px]">
        <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.store', $mpsfpProject) : route('imports.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" id="import-upload-form">
            @csrf

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Alta de importación</p>
                <h3 class="mt-2 text-lg font-semibold text-[#555555]">Prepara el archivo para entrar al flujo de preview y mapeo</h3>
                <p class="mt-1 text-sm text-gray-500">Selecciona proveedor, año de catálogo y archivo origen. El sistema te llevará después al preview para revisar columnas y contenido antes de procesar.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700">Proveedor</label>
                    <select name="supplier_id" id="supplier_id" required class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
                        <option value="">Selecciona un proveedor activo...</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">El proveedor define reglas automáticas de lectura, mapeo y normalización del catálogo.</p>
                    @error('supplier_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="catalog_year" class="block text-sm font-medium text-gray-700">Año de catálogo</label>
                    <input type="number" name="catalog_year" id="catalog_year" min="2020" max="{{ now()->year + 2 }}" value="{{ old('catalog_year', now()->year) }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Permite comparar campañas por proveedor: 2026, 2027, etc.</p>
                    @error('catalog_year')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700">Archivo origen</label>
                    <input type="file" name="file" id="file" required accept=".csv,.txt,.xlsx,.xls,.xml" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-xl file:border-0 file:bg-[#E6007E] file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-[#d1006f]">
                    <p class="mt-1 text-xs text-gray-500">Formatos aceptados: CSV, XLSX/XLS o XML. Tamaño máximo: 500 MB.</p>
                    @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4">
                <p class="text-sm font-semibold text-[#555555]">Qué pasará después</p>
                <div class="mt-3 grid gap-3 md:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Paso 1</p>
                        <p class="mt-2 text-sm text-gray-600">Preview del archivo y revisión rápida de columnas.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Paso 2</p>
                        <p class="mt-2 text-sm text-gray-600">Mapeo de campos y detección automática por proveedor.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Paso 3</p>
                        <p class="mt-2 text-sm text-gray-600">Proceso, normalización, revisión y consolidación del lote.</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-primary" id="import-upload-submit">Subir y continuar</button>
                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.index', $mpsfpProject) : route('imports.index') }}" class="btn-secondary">Volver al listado</a>
            </div>
        </form>

        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Recomendaciones del archivo</p>
                <div class="mt-4 space-y-3 text-sm text-gray-600">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="font-semibold text-[#555555]">CSV</p>
                        <p class="mt-1">Ideal para catálogos ligeros con cabecera clara y separador consistente.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="font-semibold text-[#555555]">Excel / XLSX</p>
                        <p class="mt-1">Úsalo cuando el proveedor entregue varias hojas o formato tabular complejo.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="font-semibold text-[#555555]">XML</p>
                        <p class="mt-1">Útil para catálogos estructurados con categorías, EAN e imágenes en nodos separados.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E6007E]/15 bg-[#FFF7FB] p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#E6007E]">Consejo operativo</p>
                <p class="mt-3 text-sm text-gray-600">Si vas a repetir pruebas con el mismo proveedor, mantén el año correcto y revisa después si cambió precio, referencia o EAN respecto a campañas anteriores.</p>
            </div>
        </div>
    </div>
</div>

@include('imports._activity_overlay')
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('import-upload-form');
            const submitButton = document.getElementById('import-upload-submit');
            const overlay = document.getElementById('import-activity-overlay');

            if (!form || !submitButton || !overlay) {
                return;
            }

            const titleEl = document.getElementById('import-activity-title');
            const contextEl = document.getElementById('import-activity-context');
            const subtitleEl = document.getElementById('import-activity-subtitle');
            const stageEl = document.getElementById('import-activity-stage');
            const percentEl = document.getElementById('import-activity-percent');
            const barEl = document.getElementById('import-activity-bar');
            const messageEl = document.getElementById('import-activity-message');
            const fileEl = document.getElementById('import-activity-file');
            const supplierEl = document.getElementById('import-activity-supplier');
            const actionEl = document.getElementById('import-activity-action');
            const noteEl = document.getElementById('import-activity-note');
            const errorEl = document.getElementById('import-activity-error');
            const supplierSelect = document.getElementById('supplier_id');
            const fileInput = document.getElementById('file');

            let isSubmitting = false;

            const formatPercent = (value) => Number.isFinite(value) ? value.toFixed(2) : '0.00';

            const setOverlayState = ({
                title = 'Subiendo archivo',
                context = 'Importaciones MPSFP',
                subtitle = 'El sistema está trabajando. Mantén esta pantalla abierta.',
                stage = 'Preparando subida...',
                percent = 0,
                message = 'Esperando archivo...',
                fileName = '—',
                supplierName = '—',
                action = 'Subida',
                note = 'Verás esta capa mientras haya trabajo en curso para que no parezca que la app se quedó congelada.',
                indeterminate = false,
                error = '',
            } = {}) => {
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');

                titleEl.textContent = title;
                contextEl.textContent = context;
                subtitleEl.textContent = subtitle;
                stageEl.textContent = stage;
                percentEl.textContent = formatPercent(percent);
                barEl.style.width = `${Math.max(0, Math.min(100, percent))}%`;
                barEl.classList.toggle('mp-progress-indeterminate', indeterminate);
                messageEl.textContent = message;
                fileEl.textContent = fileName;
                supplierEl.textContent = supplierName;
                actionEl.textContent = action;
                noteEl.textContent = note;

                if (error) {
                    errorEl.classList.remove('hidden');
                    errorEl.textContent = error;
                } else {
                    errorEl.classList.add('hidden');
                    errorEl.textContent = '';
                }
            };

            const releaseUi = () => {
                isSubmitting = false;
                submitButton.disabled = false;
                submitButton.textContent = 'Subir y continuar';
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();

                if (isSubmitting) {
                    return;
                }

                const selectedFile = fileInput?.files?.[0] ?? null;
                const supplierName = supplierSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Proveedor sin seleccionar';

                if (!selectedFile) {
                    form.submit();
                    return;
                }

                isSubmitting = true;
                submitButton.disabled = true;
                submitButton.textContent = 'Subiendo...';

                setOverlayState({
                    title: 'Subiendo archivo del proveedor',
                    context: `${supplierName} · ${selectedFile.name}`,
                    subtitle: 'Se mostrará el avance real de la subida y después el análisis inicial del archivo.',
                    stage: 'Conectando con el servidor...',
                    percent: 0,
                    message: 'Preparando la transferencia del archivo al servidor.',
                    fileName: selectedFile.name,
                    supplierName,
                    action: 'Subida y validación',
                    note: 'En archivos grandes verás primero el porcentaje de subida y después la fase de análisis previo.',
                    indeterminate: false,
                });

                const xhr = new XMLHttpRequest();
                xhr.open(form.method || 'POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.upload.addEventListener('progress', (progressEvent) => {
                    if (!progressEvent.lengthComputable) {
                        setOverlayState({
                            title: 'Subiendo archivo del proveedor',
                            context: `${supplierName} · ${selectedFile.name}`,
                            subtitle: 'La transferencia sigue en curso.',
                            stage: 'Subiendo archivo...',
                            percent: 0,
                            message: 'Subiendo archivo al servidor...',
                            fileName: selectedFile.name,
                            supplierName,
                            action: 'Subida y validación',
                            note: 'La transferencia sigue en curso aunque el navegador no pueda medir el porcentaje exacto.',
                            indeterminate: true,
                        });
                        return;
                    }

                    const percent = (progressEvent.loaded / progressEvent.total) * 100;
                    setOverlayState({
                        title: 'Subiendo archivo del proveedor',
                        context: `${supplierName} · ${selectedFile.name}`,
                        subtitle: 'La transferencia está en marcha.',
                        stage: 'Subiendo archivo...',
                        percent,
                        message: `Enviados ${Math.round(progressEvent.loaded / 1024 / 1024)} MB de ${Math.round(progressEvent.total / 1024 / 1024)} MB.`,
                        fileName: selectedFile.name,
                        supplierName,
                        action: 'Subida y validación',
                        note: 'Cuando el archivo llegue completo, el sistema detectará tipo, guardará la importación y abrirá el preview.',
                        indeterminate: false,
                    });
                });

                xhr.addEventListener('readystatechange', () => {
                    if (xhr.readyState >= XMLHttpRequest.HEADERS_RECEIVED) {
                        setOverlayState({
                            title: 'Analizando archivo subido',
                            context: `${supplierName} · ${selectedFile.name}`,
                            subtitle: 'La subida ya terminó. Ahora el sistema valida y prepara el preview.',
                            stage: 'Validando archivo...',
                            percent: 100,
                            message: 'Detectando tipo real, guardando importación y preparando la siguiente pantalla.',
                            fileName: selectedFile.name,
                            supplierName,
                            action: 'Análisis inicial',
                            note: 'Esta parte puede tardar un poco en XML o Excel grandes.',
                            indeterminate: true,
                        });
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 400) {
                        const responseUrl = xhr.responseURL || form.action;
                        if (responseUrl && responseUrl !== window.location.href) {
                            window.location.assign(responseUrl);
                            return;
                        }

                        document.open();
                        document.write(xhr.responseText);
                        document.close();
                        return;
                    }

                    setOverlayState({
                        title: 'No se pudo completar la subida',
                        context: `${supplierName} · ${selectedFile.name}`,
                        subtitle: 'La app no pudo terminar la operación.',
                        stage: 'Error',
                        percent: 100,
                        message: 'La subida o validación devolvió un error.',
                        fileName: selectedFile.name,
                        supplierName,
                        action: 'Error',
                        note: 'Corrige el problema y vuelve a intentarlo.',
                        indeterminate: false,
                        error: `El servidor respondió con estado ${xhr.status}.`,
                    });
                    releaseUi();
                });

                xhr.addEventListener('error', () => {
                    setOverlayState({
                        title: 'No se pudo completar la subida',
                        context: `${supplierName} · ${selectedFile.name}`,
                        subtitle: 'Hubo un problema de red o de comunicación con el servidor.',
                        stage: 'Error de conexión',
                        percent: 0,
                        message: 'La subida no pudo terminar correctamente.',
                        fileName: selectedFile.name,
                        supplierName,
                        action: 'Error',
                        note: 'Comprueba la conexión y vuelve a intentarlo.',
                        indeterminate: false,
                        error: 'No se pudo conectar con el servidor para terminar la subida.',
                    });
                    releaseUi();
                });

                xhr.send(new FormData(form));
            });
        })();
    </script>
@endpush
