<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::query();
        $user = $request->user();
        if (! $user->can('projects.view')) {
            $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        }
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $query->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'));
        $projects = $query->orderBy('name')->paginate(12);

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $this->authorize('create', Project::class);
        $users = User::where('status', 'active')->orderBy('name')->get();
        return view('projects.create', compact('users'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:projects,slug', 'regex:/^[a-z0-9\-]+$/'],
            'description' => ['nullable', 'string'],
            'public_url' => ['nullable', 'string', 'max:255'],
            'admin_url' => ['nullable', 'string', 'max:255'],
            'local_path' => ['nullable', 'string', 'max:255'],
            'project_type' => ['nullable', 'string', 'max:50'],
            'framework' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,development,paused,archived'],
            'color' => ['nullable', 'string', 'max:7'],
            'repository_url' => ['nullable', 'string', 'max:255'],
            'technical_notes' => ['nullable', 'string'],
            'has_mobile_app' => ['boolean'],
            'has_api' => ['boolean'],
            'backend_version' => ['nullable', 'string', 'max:50'],
            'mobile_app_version' => ['nullable', 'string', 'max:50'],
            'sync_status' => ['nullable', 'in:synced,pending,out_of_sync,unknown'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'access_levels' => ['nullable', 'array'],
        ]);

        $data['has_mobile_app'] = $request->boolean('has_mobile_app');
        $data['has_api'] = $request->boolean('has_api');

        $project = Project::create(collect($data)->except(['user_ids', 'access_levels'])->toArray());

        $userIds = $request->input('user_ids', []);
        $accessLevels = $request->input('access_levels', []) ?: [];
        $sync = [];
        foreach ((array) $userIds as $id) {
            $sync[$id] = ['access_level' => $accessLevels[$id] ?? 'viewer'];
        }
        $project->users()->sync($sync);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'project.created',
            'description' => 'Proyecto creado: ' . $project->name,
            'subject_type' => Project::class,
            'subject_id' => $project->id,
        ]);

        return redirect()->route('projects.index')->with('status', 'Proyecto creado correctamente.');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);
        $project->load('users', 'mobileIntegrations');
        return view('projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);
        $users = User::where('status', 'active')->orderBy('name')->get();
        $project->load('users');
        return view('projects.edit', compact('project', 'users'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', Rule::unique('projects')->ignore($project->id)],
            'description' => ['nullable', 'string'],
            'public_url' => ['nullable', 'string', 'max:255'],
            'admin_url' => ['nullable', 'string', 'max:255'],
            'local_path' => ['nullable', 'string', 'max:255'],
            'project_type' => ['nullable', 'string', 'max:50'],
            'framework' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,development,paused,archived'],
            'color' => ['nullable', 'string', 'max:7'],
            'repository_url' => ['nullable', 'string', 'max:255'],
            'technical_notes' => ['nullable', 'string'],
            'has_mobile_app' => ['boolean'],
            'has_api' => ['boolean'],
            'backend_version' => ['nullable', 'string', 'max:50'],
            'mobile_app_version' => ['nullable', 'string', 'max:50'],
            'sync_status' => ['nullable', 'in:synced,pending,out_of_sync,unknown'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $data['has_mobile_app'] = $request->boolean('has_mobile_app');
        $data['has_api'] = $request->boolean('has_api');

        $project->update(collect($data)->except(['user_ids', 'access_levels'])->toArray());

        $userIds = $request->input('user_ids', []);
        $accessLevels = $request->input('access_levels', []);
        $sync = [];
        foreach ($userIds as $id) {
            $sync[$id] = ['access_level' => $accessLevels[$id] ?? 'viewer'];
        }
        $project->users()->sync($sync);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'project.updated',
            'description' => 'Proyecto actualizado: ' . $project->name,
            'subject_type' => Project::class,
            'subject_id' => $project->id,
        ]);

        return redirect()->route('projects.show', $project)->with('status', 'Proyecto actualizado.');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);
        $name = $project->name;
        $project->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'project.deleted',
            'description' => 'Proyecto eliminado: ' . $name,
        ]);

        return redirect()->route('projects.index')->with('status', 'Proyecto eliminado.');
    }
}
