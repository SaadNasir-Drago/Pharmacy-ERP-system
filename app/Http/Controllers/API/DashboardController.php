<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Product;
use App\Models\Budget;
use App\Models\SecurityAlert;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getSystemOverview()
    {
        // Get employee statistics
        $employeeStats = [
            'total' => Employee::count(),
            'active' => Employee::where('is_active', true)->count(),
            'departments' => Employee::select('department')
                ->distinct()
                ->get()
                ->pluck('department')
        ];

        // Get order statistics
        $orderStats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'recent' => Order::with('user')
                ->latest()
                ->take(5)
                ->get()
        ];

        // Get inventory statistics
        $inventoryStats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'low_stock' => Product::where('quantity', '<', 10)->count(),
            'categories' => Product::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->get()
                ->pluck('category')
        ];

        // Get finance statistics
        $financeStats = [
            'active_budgets' => Budget::where('status', 'active')->count(),
            'total_budget_amount' => Budget::where('status', 'active')->sum('amount'),
            'recent_budgets' => Budget::with('creator')
                ->latest()
                ->take(5)
                ->get()
        ];

        // Get security statistics
        $securityStats = [
            'open_alerts' => SecurityAlert::whereIn('status', ['open', 'in_progress'])->count(),
            'critical_alerts' => SecurityAlert::where('severity', 'critical')
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
            'recent_alerts' => SecurityAlert::with(['reporter', 'assignee'])
                ->latest()
                ->take(5)
                ->get()
        ];

        // Get user statistics
        $userStats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count()
        ];

        return response()->json([
            'employee_stats' => $employeeStats,
            'order_stats' => $orderStats,
            'inventory_stats' => $inventoryStats,
            'finance_stats' => $financeStats,
            'security_stats' => $securityStats,
            'user_stats' => $userStats,
        ]);
    }
}