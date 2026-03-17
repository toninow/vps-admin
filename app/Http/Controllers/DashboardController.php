<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'active')->count();

        $projectsQuery = Project::query();
        if (! $user->can('projects.view')) {
            $projectsQuery->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        }
        $projects = $projectsQuery->with('users')->orderByRaw("FIELD(status, 'active', 'development', 'paused', 'archived')")->orderBy('name')->take(8)->get();

        $recentLogins = ActivityLog::where('action', 'login')->with('user')->latest()->take(10)->get();
        $recentActivities = ActivityLog::with('user')->latest()->take(10)->get();

        return view('dashboard.index', compact(
            'totalUsers',
            'activeUsers',
            'totalProjects',
            'activeProjects',
            'projects',
            'recentLogins',
            'recentActivities'
        ));
    }
}
