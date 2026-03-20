<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', '%' . $request->search . '%')->orWhere('email', 'like', '%' . $request->search . '%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create', User::class);
        $roles = \Spatie\Permission\Models\Role::where('guard_name', 'web')->orderBy('name')->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'status' => ['required', 'in:active,inactive'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => $data['status'],
        ]);

        if (! empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.created',
            'description' => 'Usuario creado: ' . $user->email,
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);

        return redirect()->route('users.index')->with('status', 'Usuario creado correctamente.');
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        $user->load([
            'roles',
            'projects' => fn ($query) => $query->visibleInAdmin()->orderBy('name'),
        ]);

        $recentActivity = $user->activityLogs()
            ->latest()
            ->take(8)
            ->get();

        return view('users.show', compact('user', 'recentActivity'));
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        $roles = \Spatie\Permission\Models\Role::where('guard_name', 'web')->orderBy('name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'status' => ['required', 'in:active,inactive'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,name'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->status = $data['status'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $user->syncRoles($data['roles'] ?? []);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.updated',
            'description' => 'Usuario actualizado: ' . $user->email,
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);

        return redirect()->route('users.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $email = $user->email;
        $user->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.deleted',
            'description' => 'Usuario eliminado: ' . $email,
        ]);

        return redirect()->route('users.index')->with('status', 'Usuario eliminado.');
    }
}
