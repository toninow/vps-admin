<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function legacy(Request $request)
    {
        if ($redirect = $this->normalizeLegacyRedirect($request->query('redirect'))) {
            $request->session()->put('url.intended', $redirect);
        }

        return redirect()->route('login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if ($user && $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Tu usuario está inactivo. Contacta al administrador.'],
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function normalizeLegacyRedirect(?string $redirect): ?string
    {
        $redirect = trim((string) $redirect);
        if ($redirect === '') {
            return null;
        }

        $project = Project::where('slug', 'mpsfp')->first();
        if (! $project) {
            return null;
        }

        return match (true) {
            $redirect === '/mpsfp',
            $redirect === '/mpsfp/' => route('projects.mpsfp.dashboard', $project),

            $redirect === '/mpsfp/categories',
            $redirect === '/mpsfp/categorias' => route('projects.mpsfp.categories.review', $project),

            str_starts_with($redirect, '/mpsfp/imports/') => route('projects.mpsfp.imports.show', [
                'project' => $project,
                'import' => basename($redirect),
            ]),

            $redirect === '/mpsfp/imports' => route('projects.mpsfp.imports.index', $project),
            $redirect === '/mpsfp/normalized',
            $redirect === '/mpsfp/normalizados' => route('projects.mpsfp.normalized.index', $project),

            default => null,
        };
    }
}
