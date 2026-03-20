<?php

namespace App\Http\Controllers;

use App\Models\ProductEanIssue;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class ProductEanIssueController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ProductEanIssue::class);

        $issues = ProductEanIssue::with(['normalizedProduct', 'masterProduct'])
            ->when($request->filled('supplier_id'), fn ($q) => $q->whereHas('normalizedProduct', fn ($inner) => $inner->where('supplier_id', $request->integer('supplier_id'))))
            ->when($request->filled('issue_type'), fn ($q) => $q->where('issue_type', $request->issue_type))
            ->when($request->filled('resolved'), fn ($q) => $request->boolean('resolved') ? $q->whereNotNull('resolved_at') : $q->whereNull('resolved_at'))
            ->latest()
            ->paginate(20);

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name']);

        return view('ean-issues.index', compact('issues', 'suppliers'));
    }

    public function show(ProductEanIssue $eanIssue)
    {
        $this->authorize('view', $eanIssue);
        $eanIssue->load(['normalizedProduct', 'masterProduct', 'resolvedBy']);
        return view('ean-issues.show', compact('eanIssue'));
    }

    public function bulkResolve(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('ean.resolve'), 403);

        $issues = $this->selectedIssues($request);
        $count = $issues->count();

        if ($count === 0) {
            return redirect()->back()->withErrors(['bulk' => 'No se seleccionó ninguna incidencia EAN para resolver.']);
        }

        ProductEanIssue::query()
            ->whereIn('id', $issues->pluck('id'))
            ->update([
                'resolved_at' => now(),
                'resolved_by_id' => $request->user()->id,
                'resolution_notes' => trim((string) $request->input('resolution_notes', 'Resuelta desde la revisión masiva web.')) ?: 'Resuelta desde la revisión masiva web.',
            ]);

        return redirect()->back()->with('status', "Incidencias EAN resueltas: {$count}.");
    }

    protected function selectedIssues(Request $request): Collection
    {
        $ids = collect($request->input('ean_issue_ids', []))
            ->filter(fn ($id) => ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $ids->isEmpty()
            ? collect()
            : ProductEanIssue::query()->whereIn('id', $ids)->get(['id']);
    }
}
