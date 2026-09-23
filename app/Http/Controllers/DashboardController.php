<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReferencePrice;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', ['projectCount' => Project::count(), 'priceCount' => ReferencePrice::where('is_active', true)->count(), 'recentProjects' => Project::latest()->take(5)->get()]);
    }
}
