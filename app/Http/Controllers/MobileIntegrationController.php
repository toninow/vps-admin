<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\MobileIntegration;
use App\Models\Project;
use Illuminate\Http\Request;

class MobileIntegrationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MobileIntegration::class);

        $integrations = MobileIntegration::with('project')
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->project_id))
            ->orderBy('app_name')
            ->paginate(15);

        return view('mobile-integrations.index', compact('integrations'));
    }

    public function create()
    {
        $this->authorize('create', MobileIntegration::class);
        $projects = Project::orderBy('name')->get();
        return view('mobile-integrations.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', MobileIntegration::class);

        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'app_name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'in:android,ios,flutter,react_native,other'],
            'current_version' => ['required', 'string', 'max:50'],
            'min_supported_version' => ['nullable', 'string', 'max:50'],
            'api_url' => ['nullable', 'string', 'max:255'],
            'integration_token' => ['nullable', 'string'],
            'connection_status' => ['nullable', 'in:online,offline,degraded,unknown'],
            'notes' => ['nullable', 'string'],
        ]);

        $integration = MobileIntegration::create($data);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'mobile_integration.created',
            'description' => 'Integración móvil creada: ' . $integration->app_name,
            'subject_type' => MobileIntegration::class,
            'subject_id' => $integration->id,
        ]);

        return redirect()->route('mobile-integrations.index')->with('status', 'Integración creada.');
    }

    public function show(MobileIntegration $mobileIntegration)
    {
        $this->authorize('view', $mobileIntegration);
        $mobileIntegration->load('project');
        return view('mobile-integrations.show', compact('mobileIntegration'));
    }

    public function edit(MobileIntegration $mobileIntegration)
    {
        $this->authorize('update', $mobileIntegration);
        $projects = Project::orderBy('name')->get();
        return view('mobile-integrations.edit', compact('mobileIntegration', 'projects'));
    }

    public function update(Request $request, MobileIntegration $mobileIntegration)
    {
        $this->authorize('update', $mobileIntegration);

        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'app_name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'in:android,ios,flutter,react_native,other'],
            'current_version' => ['required', 'string', 'max:50'],
            'min_supported_version' => ['nullable', 'string', 'max:50'],
            'api_url' => ['nullable', 'string', 'max:255'],
            'integration_token' => ['nullable', 'string'],
            'connection_status' => ['nullable', 'in:online,offline,degraded,unknown'],
            'notes' => ['nullable', 'string'],
        ]);

        $mobileIntegration->update($data);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'mobile_integration.updated',
            'description' => 'Integración móvil actualizada: ' . $mobileIntegration->app_name,
            'subject_type' => MobileIntegration::class,
            'subject_id' => $mobileIntegration->id,
        ]);

        return redirect()->route('mobile-integrations.index')->with('status', 'Integración actualizada.');
    }

    public function destroy(MobileIntegration $mobileIntegration)
    {
        $this->authorize('delete', $mobileIntegration);
        $name = $mobileIntegration->app_name;
        $mobileIntegration->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'mobile_integration.deleted',
            'description' => 'Integración móvil eliminada: ' . $name,
        ]);

        return redirect()->route('mobile-integrations.index')->with('status', 'Integración eliminada.');
    }
}
