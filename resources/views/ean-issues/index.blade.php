@extends('layouts.app')

@section('title', 'Incidencias EAN')
@section('page_title', 'Incidencias EAN')

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ route('ean-issues.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <select name="supplier_id" class="w-full rounded-md border-gray-300 sm:text-sm">
            <option value="">Todos los proveedores</option>
            @foreach (($suppliers ?? collect()) as $supplier)
                <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
            @endforeach
        </select>
        <select name="issue_type" class="w-full rounded-md border-gray-300 sm:text-sm">
            <option value="">Todos los tipos</option>
            <option value="empty" {{ request('issue_type') === 'empty' ? 'selected' : '' }}>EAN vacío</option>
            <option value="invalid_length" {{ request('issue_type') === 'invalid_length' ? 'selected' : '' }}>Longitud incorrecta</option>
            <option value="invalid_chars" {{ request('issue_type') === 'invalid_chars' ? 'selected' : '' }}>Caracteres no válidos</option>
            <option value="invalid_checksum" {{ request('issue_type') === 'invalid_checksum' ? 'selected' : '' }}>Checksum inválido</option>
            <option value="upc_or_other" {{ request('issue_type') === 'upc_or_other' ? 'selected' : '' }}>UPC u otro</option>
        </select>
        <select name="resolved" class="w-full rounded-md border-gray-300 sm:text-sm">
            <option value="">Todos</option>
            <option value="0" {{ request('resolved') === '0' ? 'selected' : '' }}>Pendientes</option>
            <option value="1" {{ request('resolved') === '1' ? 'selected' : '' }}>Resueltas</option>
        </select>
        <button type="submit" class="btn-secondary">Filtrar</button>
    </form>

    <form method="POST" action="{{ route('ean-issues.bulk-resolve') }}" id="ean-bulk-form" class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        @csrf
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-4">
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" id="ean-select-all" class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                    <span>Seleccionar todo en esta página</span>
                </label>
                <span id="ean-selected-count">0 seleccionadas</span>
            </div>
            @if (auth()->user()->can('ean.resolve'))
                <div class="flex flex-wrap items-center gap-2">
                    <input type="text" name="resolution_notes" value="Resuelta desde la revisión masiva web." class="w-full min-w-[280px] rounded-md border-gray-300 sm:text-sm" placeholder="Nota de resolución">
                    <button type="submit" class="btn-primary">Resolver seleccionadas</button>
                </div>
            @endif
        </div>
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"></th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Proveedor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Valor recibido</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Producto</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Resuelto</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($issues as $issue)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <input type="checkbox" name="ean_issue_ids[]" value="{{ $issue->id }}" class="ean-row-checkbox rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $issue->normalizedProduct?->supplier?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700">{{ $issue->issue_type }}</span></td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $issue->value_received ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-[#555555] max-w-xs truncate">
                            @if ($issue->normalizedProduct)
                                <a href="{{ route('products.normalized.show', $issue->normalizedProduct) }}" class="btn-link">{{ $issue->normalizedProduct->name ?? 'ID ' . $issue->normalized_product_id }}</a>
                            @else
                                {{ $issue->masterProduct->name ?? '—' }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">{{ $issue->resolved_at ? $issue->resolved_at->format('d/m/Y') : 'Pendiente' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <a href="{{ route('ean-issues.show', $issue) }}" class="btn-link">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay incidencias EAN.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </form>

    <div class="flex justify-center">{{ $issues->withQueryString()->links() }}</div>
</div>

<script>
    (() => {
        const form = document.getElementById('ean-bulk-form');
        if (!form) return;
        const selectAll = document.getElementById('ean-select-all');
        const checkboxes = Array.from(form.querySelectorAll('.ean-row-checkbox'));
        const counter = document.getElementById('ean-selected-count');

        const syncCount = () => {
            const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
            if (counter) counter.textContent = `${selected} seleccionadas`;
            if (selectAll) selectAll.checked = selected > 0 && selected === checkboxes.length;
        };

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
                syncCount();
            });
        }

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', syncCount));
        syncCount();
    })();
</script>
@endsection
