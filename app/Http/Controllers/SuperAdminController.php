<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\GlobalSetting;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SuperAdminController extends Controller
{
    /**
     * Get super admin dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('isSuperAdmin', $request->user());

        $totalMembers = User::where('role', 'member')->count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalLoans = \App\Models\Loan::count();
        $activeLoans = \App\Models\Loan::where('status', 'active')->count();
        $totalTransactions = \App\Models\Transaction::count();

        $recentAuditLogs = AuditLog::with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user?->full_name,
                    'action' => $log->action,
                    'modelType' => $log->model_type,
                    'timestamp' => $log->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'totalMembers' => $totalMembers,
                'totalAdmins' => $totalAdmins,
                'totalLoans' => $totalLoans,
                'activeLoans' => $activeLoans,
                'totalTransactions' => $totalTransactions,
                'recentAuditLogs' => $recentAuditLogs,
            ],
        ], 200);
    }

    /**
     * Get global settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $this->authorize('isSuperAdmin', $request->user());

        $settings = GlobalSetting::all()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->value];
            });

        return response()->json([
            'success' => true,
            'data' => $settings,
        ], 200);
    }

    /**
     * Update global settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $this->authorize('isSuperAdmin', $request->user());

        try {
            $validated = $request->validate([
                'default_loan_interest_rate' => 'sometimes|numeric|min:0|max:100',
                'max_loan_amount' => 'sometimes|numeric|min:0',
                'min_loan_amount' => 'sometimes|numeric|min:0',
                'contribution_cycle_days' => 'sometimes|integer|min:1',
                'savings_interest_rate' => 'sometimes|numeric|min:0|max:100',
                'maintenance_mode' => 'sometimes|boolean',
                'enable_2fa' => 'sometimes|boolean',
                'max_failed_login_attempts' => 'sometimes|integer|min:1',
                'session_timeout_minutes' => 'sometimes|integer|min:1',
            ]);

            foreach ($validated as $key => $value) {
                GlobalSetting::set($key, $value);
            }

            AuditLog::log('SETTINGS_UPDATED', 'GlobalSetting', null, [], $validated);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get all admins
     */
    public function getAdmins(Request $request): JsonResponse
    {
        $this->authorize('isSuperAdmin', $request->user());

        $admins = User::where('role', 'admin')
            ->select('id', 'first_name', 'last_name', 'email', 'phone', 'is_active', 'created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $admins,
        ], 200);
    }

    /**
     * Create new admin
     */
    public function createAdmin(Request $request): JsonResponse
    {
        $this->authorize('isSuperAdmin', $request->user());

        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users',
                'phone' => 'required|string|unique:users',
                'password' => 'required|string|min:8',
            ]);

            $admin = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
                'country' => 'N/A',
                'kyc_status' => 'verified',
                'is_active' => true,
            ]);

            AuditLog::log('ADMIN_CREATED', 'User', $admin->id, [], [
                'email' => $admin->email,
                'name' => $admin->full_name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Admin created successfully',
                'data' => [
                    'id' => $admin->id,
                    'email' => $admin->email,
                    'name' => $admin->full_name,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Update admin
     */
    public function updateAdmin(Request $request, $id): JsonResponse
    {
        $this->authorize('isSuperAdmin', $request->user());

        try {
            $admin = User::findOrFail($id);

            if ($admin->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not an admin',
                ], 400);
            }

            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|unique:users,phone,' . $id,
                'is_active' => 'sometimes|boolean',
            ]);

            $oldValues = $admin->only(array_keys($validated));
            $admin->update($validated);

            AuditLog::log('ADMIN_UPDATED', 'User', $id, $oldValues, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Admin updated successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Delete admin
     */
    public function deleteAdmin(Request $request, $id): JsonResponse
    {
        $this->authorize('isSuperAdmin', $request->user());

        $admin = User::findOrFail($id);

        if ($admin->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'User is not an admin',
            ], 400);
        }

        if ($admin->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own admin account',
            ], 400);
        }

        $admin->delete();

        AuditLog::log('ADMIN_DELETED', 'User', $id);

        return response()->json([
            'success' => true,
            'message' => 'Admin deleted successfully',
        ], 200);
    }

    /**
     * Get audit logs
     */
    public function getAuditLogs(Request $request): JsonResponse
    {
        $this->authorize('isSuperAdmin', $request->user());

        $limit = $request->query('limit', 100);
        $offset = $request->query('offset', 0);
        $action = $request->query('action', '');

        $query = AuditLog::with('user');

        if ($action) {
            $query->where('action', 'like', "%{$action}%");
        }

        $logs = $query->latest()
            ->paginate($limit, ['*'], 'page', ($offset / $limit) + 1);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'limit' => $limit,
                'offset' => $offset,
            ],
        ], 200);
    }

    /**
     * Get system statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $this->authorize('isSuperAdmin', $request->user());

        $memberGrowth = User::where('role', 'member')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        $loanStats = \App\Models\Loan::selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('status')
            ->get();

        $transactionStats = \App\Models\Transaction::selectRaw('type, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'memberGrowth' => $memberGrowth,
                'loanStats' => $loanStats,
                'transactionStats' => $transactionStats,
            ],
        ], 200);
    }
}
