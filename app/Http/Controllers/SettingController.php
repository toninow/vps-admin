<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Setting::class);

        $settings = Setting::orderBy('key')->get();

        return view('settings.index', compact('settings'));
    }
}
