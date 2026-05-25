<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Product;
use App\Models\Admin;
use App\Models\Generation;
use App\Models\Funnel;
use App\Models\Member;

/**
 * Controller do Dashboard
 */
class DashboardController
{
    /**
     * Dashboard principal
     */
    public function index(): void
    {
        Auth::require();

        $stats = [
            'funnels' => Funnel::count(),
            'products' => Product::count(),
            'members' => Member::count(),
            'admins' => Admin::count(),
            'generations' => Generation::count()
        ];

        $recentGenerations = Generation::recent(5);

        view('admin.dashboard', [
            'stats' => $stats,
            'recentGenerations' => $recentGenerations,
            'user' => Auth::user()
        ]);
    }
}
