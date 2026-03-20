@php
    $ean = $normalizedProduct->ean13;
    $barcodeStatus = strtolower((string) ($normalizedProduct->barcode_status ?? $normalizedProduct->ean_status ?? 'missing'));
    $validationStatus = strtolower((string) ($normalizedProduct->validation_status ?? 'pending'));
    $badgeClass = match ($barcodeStatus) {
        'ok' => 'status-green',
        'non_ean' => 'status-blue',
        'invalid_ean' => 'status-amber',
        default => 'status-gray',
    };
    $validationClass = match ($validationStatus) {
        'ok' => 'status-green',
        'warning' => 'status-amber',
        'error' => 'status-red',
        default => 'status-gray',
    };
    $imageCandidates = $imageCandidates ?? [];
    $storedImageUrls = $storedImageUrls ?? [];
    $rawImageUrls = $rawImageUrls ?? [];
    $extraRawUrls = array_values(array_diff($rawImageUrls, $storedImageUrls));
    $displaySummary = trim((string) ($normalizedProduct->summary ?? ''));
    $summaryWordCount = str_word_count(str_replace(['-', '_', '/'], ' ', $displaySummary));
    $summaryLooksLikeCode = $displaySummary !== ''
        && ! str_contains($displaySummary, ' ')
        && preg_match('/^[A-Z0-9][A-Z0-9._\\/-]{3,}$/', $displaySummary);
    if (($displaySummary === '' || $summaryLooksLikeCode || $summaryWordCount < 2) && ! empty($normalizedProduct->name)) {
        $displaySummary = (string) $normalizedProduct->name;
    }

    $descriptionSource = trim((string) ($normalizedProduct->description ?? ''));
    $descriptionSource = (string) preg_replace('/^CARACTER[IÍ]STICAS:\s*/ui', '', $descriptionSource);
    $descriptionItems = collect(preg_split('/\s*;\s*|\r\n|\r|\n/u', $descriptionSource) ?: [])
        ->map(fn ($item) => trim(strip_tags((string) $item)))
        ->filter()
        ->values();
    $showDescriptionList = $descriptionItems->count() > 1;

    $tagItems = collect(preg_split('/\s*[,;|]+\s*/u', (string) ($normalizedProduct->tags ?? '')) ?: [])
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->values();

    $categoryStatus = strtolower((string) ($normalizedProduct->category_status ?? 'unassigned'));
    $categoryStatusClass = match ($categoryStatus) {
        'confirmed' => 'status-green',
        'suggested' => 'status-blue',
        default => 'status-amber',
    };
    $categoryStatusLabel = match ($categoryStatus) {
        'confirmed' => 'Categoría confirmada',
        'suggested' => 'Categoría sugerida',
        default => 'Categoría pendiente',
    };

    $categoryRouteParts = collect($normalizedProduct->formattedCategoryPath() !== '' ? explode(', ', $normalizedProduct->formattedCategoryPath()) : [])
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->values();

    $openEanIssues = $normalizedProduct->productEanIssues->where('resolved_at', null)->values();
    $suggestedCategories = $normalizedProduct->productCategorySuggestions->sortByDesc('score')->values();
    $primarySuggestion = $suggestedCategories->first();
    $priceSale = $normalizedProduct->price_tax_incl !== null ? number_format((float) $normalizedProduct->price_tax_incl, 2, ',', '.') . ' €' : '';
    $priceCost = $normalizedProduct->cost_price !== null ? number_format((float) $normalizedProduct->cost_price, 2, ',', '.') . ' €' : '';
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-[1.75rem] border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 bg-gradient-to-r from-white via-[#fff7fb] to-white px-6 py-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400">Catálogo / Producto normalizado</p>
                    <h3 class="mt-2 text-2xl font-semibold leading-tight text-[#2f3441]">
                        {{ $normalizedProduct->name ?: 'Producto sin nombre' }}
                    </h3>
                    <p class="mt-2 max-w-4xl text-sm text-gray-500">
                        {{ $displaySummary ?: 'Este producto todavía no tiene resumen comercial sólido.' }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="status-badge {{ $validationClass }}">Validación {{ $validationStatus }}</span>
                        <span class="status-badge {{ $badgeClass }}">
                            @if ($ean)
                                EAN {{ $ean }}
                            @elseif ($barcodeStatus === 'missing')
                                Sin código
                            @else
                                {{ $normalizedProduct->barcodeStatusLabel() }}
                            @endif
                        </span>
                        <span class="status-badge {{ $categoryStatusClass }}">{{ $categoryStatusLabel }}</span>
                        @if ($normalizedProduct->masterProduct)
                            <span class="status-badge status-blue">Enlazado a catálogo maestro</span>
                        @else
                            <span class="status-badge status-amber">Aún sin maestro</span>
                        @endif
                        <span class="status-badge status-gray">Proveedor {{ $normalizedProduct->supplier->name ?? '—' }}</span>
                    </div>
                </div>

                <div class="grid gap-2 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-600 md:min-w-[280px]">
                    <div><span class="font-medium text-[#2f3441]">ID normalizado:</span> #{{ $normalizedProduct->id }}</div>
                    <div><span class="font-medium text-[#2f3441]">Ref. proveedor:</span> {{ $normalizedProduct->supplier_reference ?: '—' }}</div>
                    <div><span class="font-medium text-[#2f3441]">Importación:</span> {{ $normalizedProduct->supplierImport->filename_original ?? '—' }}</div>
                    <div><span class="font-medium text-[#2f3441]">Actualizado:</span> {{ $normalizedProduct->updated_at?->format('d/m/Y H:i') ?? '—' }}</div>
                </div>
            </div>
        </div>

        <div class="border-b border-gray-200 bg-gray-50 px-6 py-3">
            <div class="flex flex-wrap gap-2 text-xs font-semibold text-gray-500">
                <a href="#producto-general" class="rounded-full border border-gray-200 bg-white px-3 py-1.5 hover:border-[#E6007E] hover:text-[#E6007E]">General</a>
                <a href="#producto-catalogo" class="rounded-full border border-gray-200 bg-white px-3 py-1.5 hover:border-[#E6007E] hover:text-[#E6007E]">Catálogo</a>
                <a href="#producto-contenido" class="rounded-full border border-gray-200 bg-white px-3 py-1.5 hover:border-[#E6007E] hover:text-[#E6007E]">Contenido</a>
                <a href="#producto-trazabilidad" class="rounded-full border border-gray-200 bg-white px-3 py-1.5 hover:border-[#E6007E] hover:text-[#E6007E]">Trazabilidad</a>
                <a href="#producto-datos-origen" class="rounded-full border border-gray-200 bg-white px-3 py-1.5 hover:border-[#E6007E] hover:text-[#E6007E]">Origen</a>
            </div>
        </div>

        <div id="producto-general" class="grid gap-6 p-6 xl:grid-cols-[1.4fr_0.9fr]">
            <div class="space-y-4">
                <div class="rounded-[1.5rem] border border-gray-200 bg-gray-50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Imágenes del producto</p>
                            <h4 class="mt-1 text-base font-semibold text-[#2f3441]">Galería principal</h4>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="status-badge status-blue">{{ count($imageCandidates) }} detectadas</span>
                            <span class="status-badge status-gray">{{ count($storedImageUrls) }} guardadas</span>
                            @if (! empty($extraRawUrls))
                                <span class="status-badge status-amber">{{ count($extraRawUrls) }} extra raw</span>
                            @endif
                        </div>
                    </div>

                    @if (! empty($extraRawUrls))
                        <div class="mt-4 alert alert-warn">
                            Hay URLs adicionales detectadas en el `raw_data` del proveedor. Se muestran aquí para revisión.
                        </div>
                    @endif

                    @if (! empty($imageCandidates))
                        @php
                            $galleryId = 'normalized-gallery-' . $normalizedProduct->id . '-' . substr(md5(implode('|', $imageCandidates)), 0, 8);
                        @endphp
                        <div class="mt-4" data-gallery-root id="{{ $galleryId }}">
                            <div class="overflow-hidden rounded-[1.5rem] border border-gray-200 bg-white shadow-sm">
                                <div class="relative flex min-h-[430px] items-center justify-center bg-white px-6 py-6">
                                    <img src="{{ $imageCandidates[0] }}"
                                         alt="Imagen principal"
                                         data-gallery-main
                                         class="max-h-[65vh] w-full object-contain">
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="status-badge status-blue" data-gallery-counter>1 / {{ count($imageCandidates) }}</span>
                                        <span class="text-xs text-gray-500">Vista principal del producto</span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" class="btn-secondary" data-gallery-prev>Anterior</button>
                                        <button type="button" class="btn-secondary" data-gallery-next>Siguiente</button>
                                        <a href="{{ $imageCandidates[0] }}" target="_blank" rel="noopener" class="btn-primary" data-gallery-open>Abrir original</a>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-[1.5rem] border border-gray-200 bg-white p-3">
                                <div class="flex gap-3 overflow-x-auto pb-1" data-gallery-strip>
                                    @foreach ($imageCandidates as $index => $url)
                                        <button type="button"
                                                class="group shrink-0 overflow-hidden rounded-2xl border border-gray-200 bg-white transition hover:border-[#E6007E]/40"
                                                data-gallery-thumb
                                                data-image-index="{{ $index }}"
                                                data-image-url="{{ $url }}">
                                            <img src="{{ $url }}" alt="Miniatura {{ $index + 1 }}" class="h-24 w-24 object-cover sm:h-28 sm:w-28">
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 rounded-[1.5rem] border border-dashed border-gray-300 bg-white px-6 py-10 text-center text-sm text-gray-500">
                            El proveedor no dejó una imagen válida para este producto.
                        </div>
                    @endif
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-[1.5rem] border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Resumen de ficha</p>
                    <div class="mt-4 grid gap-3 text-sm text-gray-600">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">Marca:</span> {{ $normalizedProduct->brand ?: '—' }}</div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">Precio venta al público:</span> {{ $priceSale }}</div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">Precio proveedor:</span> {{ $priceCost }}</div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">Stock origen:</span> {{ $normalizedProduct->quantity ?? 0 }}</div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">Activo:</span> {{ (int) ($normalizedProduct->active ?? 0) }}</div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">Almacén:</span> {{ $normalizedProduct->warehouse ?: '—' }}</div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">Regla impuestos:</span> {{ $normalizedProduct->tax_rule_id ?? '—' }}</div>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Estado de catálogo</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="status-badge {{ $categoryStatusClass }}">{{ $categoryStatusLabel }}</span>
                        @if ($tagItems->isNotEmpty())
                            <span class="status-badge status-green">{{ $tagItems->count() }} etiquetas</span>
                        @else
                            <span class="status-badge status-amber">Sin etiquetas</span>
                        @endif
                        @if ($normalizedProduct->masterProduct)
                            <span class="status-badge status-blue">Catálogo maestro enlazado</span>
                        @else
                            <span class="status-badge status-amber">Pendiente de consolidación</span>
                        @endif
                    </div>
                    <div class="mt-4 space-y-3 text-sm text-gray-600">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">EAN13:</span> {{ $normalizedProduct->ean13 ?: '—' }}</div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">barcode_raw:</span> {{ $normalizedProduct->barcode_raw ?: '—' }}</div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">barcode_status:</span> {{ $normalizedProduct->barcodeStatusLabel() }}</div>
                        @if ($normalizedProduct->masterProduct)
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                                <span class="font-medium text-[#2f3441]">Producto maestro:</span>
                                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.master.show', [$mpsfpProject, $normalizedProduct->masterProduct]) : route('products.master.show', $normalizedProduct->masterProduct) }}" class="btn-link">
                                    {{ $normalizedProduct->masterProduct->name ?? ('#' . $normalizedProduct->master_product_id) }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section id="producto-catalogo" class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Catálogo y clasificación</p>
                    <h4 class="mt-1 text-lg font-semibold text-[#2f3441]">Ruta de categoría</h4>
                </div>
                <span class="status-badge {{ $categoryStatusClass }}">{{ $categoryStatusLabel }}</span>
            </div>

            <div class="mt-4 rounded-[1.25rem] border border-gray-200 bg-gray-50 p-4">
                @if ($categoryRouteParts->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($categoryRouteParts as $part)
                            <span class="rounded-full border border-[#E6007E]/20 bg-[#E6007E]/8 px-3 py-1 text-xs font-semibold text-[#E6007E]">{{ $part }}</span>
                        @endforeach
                    </div>
                    <p class="mt-3 text-sm text-gray-500">
                        {{ $categoryStatus === 'confirmed'
                            ? 'Ruta final ya confirmada.'
                            : 'Ruta aplicada como sugerencia. La app es quien confirma o corrige la ruta final.' }}
                    </p>
                @else
                    <div class="alert alert-warn">Este producto todavía no tiene una ruta de categoría útil.</div>
                @endif
            </div>

            <div class="mt-5 rounded-[1.25rem] border border-gray-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h5 class="text-sm font-semibold text-[#2f3441]">Etiquetas del producto</h5>
                    @if ($tagItems->isNotEmpty())
                        <span class="status-badge status-green">{{ $tagItems->count() }} etiquetas</span>
                    @endif
                </div>
                @if ($tagItems->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($tagItems as $tag)
                            <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold text-[#2f3441]">{{ $tag }}</span>
                        @endforeach
                    </div>
                @else
                    <div class="mt-3 alert alert-warn">Este producto aún no tiene etiquetas generadas.</div>
                @endif
            </div>
        </div>

        <div class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Sugerencias automáticas</p>
                    <h4 class="mt-1 text-lg font-semibold text-[#2f3441]">Candidatas del sistema</h4>
                </div>
                @if ($suggestedCategories->isNotEmpty())
                    <span class="status-badge status-blue">{{ $suggestedCategories->count() }} sugerencias</span>
                @endif
            </div>

            @if ($suggestedCategories->isNotEmpty())
                <div class="mt-4 space-y-3">
                    @foreach ($suggestedCategories->take(5) as $sug)
                        <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-[#2f3441]">{{ $sug->category->name ?? '—' }}</p>
                                    @if ($sug->category?->parent)
                                        <p class="mt-1 text-xs text-gray-500">Padre: {{ $sug->category->parent->name }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="status-badge status-blue">score {{ number_format((float) $sug->score, 2) }}</span>
                                    @if ($sug->accepted_at)
                                        <span class="status-badge status-green">Seleccionada</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif ($primarySuggestion)
                <div class="mt-4 alert alert-info">El sistema detectó una sugerencia, pero no pudo cargar el detalle completo.</div>
            @else
                <div class="mt-4 alert alert-warn">Este producto todavía no tiene sugerencias de categoría generadas.</div>
            @endif
        </div>
    </section>

    <section id="producto-contenido" class="grid gap-6 xl:grid-cols-[1fr_0.9fr]">
        <div class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Contenido comercial</p>
            <div class="mt-4 space-y-4">
                <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50 p-4">
                    <h4 class="text-sm font-semibold text-[#2f3441]">Resumen</h4>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $displaySummary ?: 'Sin resumen disponible.' }}</p>
                </div>
                <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50 p-4">
                    <h4 class="text-sm font-semibold text-[#2f3441]">Descripción</h4>
                    @if ($showDescriptionList)
                        <ul class="mt-3 space-y-2 text-sm text-gray-600">
                            @foreach ($descriptionItems as $item)
                                <li class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2">
                                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#E6007E]"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $normalizedProduct->description ?: 'Sin descripción disponible.' }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Validación técnica</p>
            <div class="mt-4 space-y-3 text-sm text-gray-600">
                <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">Validación:</span> {{ $validationStatus }}</div>
                <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">EAN status:</span> {{ $normalizedProduct->ean_status ?: '—' }}</div>
                <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50 px-4 py-3"><span class="font-medium text-[#2f3441]">barcode_type:</span> {{ $normalizedProduct->barcodeTypeLabel() }}</div>
                @if ($openEanIssues->isNotEmpty())
                    <div class="rounded-[1.25rem] border border-amber-200 bg-amber-50 p-4">
                        <h4 class="text-sm font-semibold text-amber-800">Incidencias EAN abiertas</h4>
                        <ul class="mt-2 space-y-1 text-sm text-amber-900">
                            @foreach ($openEanIssues as $issue)
                                <li>
                                    <a href="{{ route('ean-issues.show', $issue) }}" class="btn-link">{{ $issue->issue_type }}</a>:
                                    {{ $issue->value_received ?? '—' }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section id="producto-trazabilidad" class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Trazabilidad</p>
        <div class="mt-4 grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-600"><span class="font-medium text-[#2f3441]">Proveedor:</span> {{ $normalizedProduct->supplier->name ?? '—' }}</div>
            <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-600"><span class="font-medium text-[#2f3441]">Importación:</span> {{ $normalizedProduct->supplierImport->filename_original ?? '—' }}</div>
            <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-600"><span class="font-medium text-[#2f3441]">Creado:</span> {{ $normalizedProduct->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
            <div class="rounded-[1.25rem] border border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-600"><span class="font-medium text-[#2f3441]">Actualizado:</span> {{ $normalizedProduct->updated_at?->format('d/m/Y H:i') ?? '—' }}</div>
        </div>
    </section>

    @if ($normalizedProduct->supplierImportRow?->raw_data)
        <section id="producto-datos-origen" class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
            <details>
                <summary class="cursor-pointer text-sm font-semibold text-[#2f3441]">Ver datos originales del proveedor (`raw_data`)</summary>
                <pre class="mt-4 overflow-x-auto rounded-[1.25rem] bg-gray-950 p-4 text-xs text-gray-100">{{ json_encode($normalizedProduct->supplierImportRow->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </details>
        </section>
    @endif
</div>
