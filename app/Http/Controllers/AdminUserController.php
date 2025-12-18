<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\AdminRole;
use App\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    /**
     * Get all admin users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AdminUser::with('user', 'role', 'assignedBy');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by role
            if ($request->has('role_id')) {
                $query->byRole($request->role_id);
            }

            // Search by user email or name
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            }

            $adminUsers = $query->paginate($request->get('per_page', 15));

            return ApiResponse::success('Admin users retrieved successfully', $adminUsers);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve admin users', 500, $e->getMessage());
        }
    }

    /**
     * Get a single admin user
     */
    public function show(AdminUser $adminUser): JsonResponse
    {
        try {
            $adminUser->load('user', 'role', 'assignedBy');
            return ApiResponse::success('Admin user retrieved successfully', $adminUser);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve admin user', 500, $e->getMessage());
        }
    }

    /**
     * Assign a role to a user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'role_id' => 'required|exists:admin_roles,id',
                'notes' => 'nullable|string',
            ]);

            // Check if user already has this role
            $existing = AdminUser::where('user_id', $validated['user_id'])
                ->where('role_id', $validated['role_id'])
                ->first();

            if ($existing) {
                return ApiResponse::error('User already has this role', 400);
            }

            // Check if user already has an admin role
            $currentAdmin = AdminUser::where('user_id', $validated['user_id'])->first();
            if ($currentAdmin) {
                return ApiResponse::error('User already has an admin role. Remove the current role first.', 400);
            }

            $adminUser = AdminUser::create([
                'user_id' => $validated['user_id'],
                'role_id' => $validated['role_id'],
                'notes' => $validated['notes'] ?? null,
                'assigned_by' => auth()->id(),
                'status' => 'active',
            ]);

            $adminUser->load('user', 'role', 'assignedBy');

            return ApiResponse::success('Role assigned successfully', $adminUser, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to assign role', 500, $e->getMessage());
        }
    }

    /**
     * Update an admin user
     */
    public function update(Request $request, AdminUser $adminUser): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role_id' => 'sometimes|exists:admin_roles,id',
                'status' => 'sometimes|in:active,inactive,suspended',
                'notes' => 'nullable|string',
            ]);

            // If changing role, check if new role exists
            if (isset($validated['role_id']) && $validated['role_id'] !== $adminUser->role_id) {
                $newRole = AdminRole::find($validated['role_id']);
                if (!$newRole) {
                    return ApiResponse::error('Role not found', 404);
                }
            }

            $adminUser->update($validated);
            $adminUser->load('user', 'role', 'assignedBy');

            return ApiResponse::success('Admin user updated successfully', $adminUser);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update admin user', 500, $e->getMessage());
        }
    }

    /**
     * Remove admin role from user
     */
    public function destroy(AdminUser $adminUser): JsonResponse
    {
        try {
            $adminUser->delete();
            return ApiResponse::success('Admin role removed successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to remove admin role', 500, $e->getMessage());
        }
    }

    /**
     * Activate an admin user
     */
    public function activate(AdminUser $adminUser): JsonResponse
    {
        try {
            $adminUser->activate();
            return ApiResponse::success('Admin user activated successfully', $adminUser);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to activate admin user', 500, $e->getMessage());
        }
    }

    /**
     * Deactivate an admin user
     */
    public function deactivate(AdminUser $adminUser): JsonResponse
    {
        try {
            $adminUser->deactivate();
            return ApiResponse::success('Admin user deactivated successfully', $adminUser);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to deactivate admin user', 500, $e->getMessage());
        }
    }

    /**
     * Suspend an admin user
     */
    public function suspend(AdminUser $adminUser): JsonResponse
    {
        try {
            $adminUser->suspend();
            return ApiResponse::success('Admin user suspended successfully', $adminUser);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to suspend admin user', 500, $e->getMessage());
        }
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(User $user): JsonResponse
    {
        try {
            $adminUser = AdminUser::where('user_id', $user->id)->with('role')->first();

            if (!$adminUser) {
                return ApiResponse::success('User is not an admin', [
                    'is_admin' => false,
                    'user_id' => $user->id,
                ]);
            }

            return ApiResponse::success('User is an admin', [
                'is_admin' => true,
                'user_id' => $user->id,
                'role' => $adminUser->role,
                'status' => $adminUser->status,
                'permissions' => $adminUser->role->permissions,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to check admin status', 500, $e->getMessage());
        }
    }

    /**
     * Get admin user by user ID
     */
    public function getByUserId(int $userId): JsonResponse
    {
        try {
            $adminUser = AdminUser::where('user_id', $userId)
                ->with('user', 'role', 'assignedBy')
                ->first();

            if (!$adminUser) {
                return ApiResponse::error('Admin user not found', 404);
            }

            return ApiResponse::success('Admin user retrieved successfully', $adminUser);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve admin user', 500, $e->getMessage());
        }
    }
}
