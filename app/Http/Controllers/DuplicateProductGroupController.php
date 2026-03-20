<?php

namespace App\Http\Controllers;

use App\Models\DuplicateProductGroup;
use Illuminate\Http\Request;

class DuplicateProductGroupController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DuplicateProductGroup::class);

        $groups = DuplicateProductGroup::with(['masterProduct', 'duplicateProductGroupItems'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15);

        return view('duplicates.index', compact('groups'));
    }

    public function show(DuplicateProductGroup $duplicate)
    {
        $this->authorize('view', $duplicate);
        $duplicate->load(['masterProduct', 'duplicateProductGroupItems.normalizedProduct', 'duplicateProductGroupItems.masterProduct']);
        return view('duplicates.show', compact('duplicate'));
    }
}
