<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminRoleController extends Controller
{
    /**
     * Get all admin roles
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AdminRole::query();

            // Filter by status
            if ($request->has('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->inactive();
                }
            }

            // Search by name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            }

            $roles = $query->byLevel()->paginate($request->get('per_page', 15));

            return ApiResponse::success('Admin roles retrieved successfully', $roles);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve admin roles', 500, $e->getMessage());
        }
    }

    /**
     * Get a single admin role
     */
    public function show(AdminRole $adminRole): JsonResponse
    {
        try {
            $adminRole->load('adminUsers.user');
            return ApiResponse::success('Admin role retrieved successfully', $adminRole);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve admin role', 500, $e->getMessage());
        }
    }

    /**
     * Create a new admin role
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:admin_roles',
                'slug' => 'required|string|unique:admin_roles',
                'description' => 'nullable|string',
                'level' => 'required|integer|min:0',
                'permissions' => 'required|array',
                'permissions.*' => 'string',
            ]);

            $role = AdminRole::create($validated);

            return ApiResponse::success('Admin role created successfully', $role, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create admin role', 500, $e->getMessage());
        }
    }

    /**
     * Update an admin role
     */
    public function update(Request $request, AdminRole $adminRole): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|unique:admin_roles,name,' . $adminRole->id,
                'slug' => 'sometimes|string|unique:admin_roles,slug,' . $adminRole->id,
                'description' => 'nullable|string',
                'level' => 'sometimes|integer|min:0',
                'permissions' => 'sometimes|array',
                'permissions.*' => 'string',
                'is_active' => 'sometimes|boolean',
            ]);

            $adminRole->update($validated);

            return ApiResponse::success('Admin role updated successfully', $adminRole);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update admin role', 500, $e->getMessage());
        }
    }

    /**
     * Delete an admin role
     */
    public function destroy(AdminRole $adminRole): JsonResponse
    {
        try {
            // Check if role has active admin users
            if ($adminRole->adminUsers()->active()->exists()) {
                return ApiResponse::error('Cannot delete role with active admin users', 400);
            }

            $adminRole->delete();
            return ApiResponse::success('Admin role deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete admin role', 500, $e->getMessage());
        }
    }

    /**
     * Add permission to role
     */
    public function addPermission(Request $request, AdminRole $adminRole): JsonResponse
    {
        try {
            $validated = $request->validate([
                'permission' => 'required|string',
            ]);

            $adminRole->addPermission($validated['permission']);

            return ApiResponse::success('Permission added successfully', $adminRole);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to add permission', 500, $e->getMessage());
        }
    }

    /**
     * Remove permission from role
     */
    public function removePermission(Request $request, AdminRole $adminRole): JsonResponse
    {
        try {
            $validated = $request->validate([
                'permission' => 'required|string',
            ]);

            $adminRole->removePermission($validated['permission']);

            return ApiResponse::success('Permission removed successfully', $adminRole);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to remove permission', 500, $e->getMessage());
        }
    }

    /**
     * Get all users with a specific role
     */
    public function users(AdminRole $adminRole, Request $request): JsonResponse
    {
        try {
            $users = $adminRole->adminUsers()
                ->with('user')
                ->paginate($request->get('per_page', 15));

            return ApiResponse::success('Role users retrieved successfully', $users);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve role users', 500, $e->getMessage());
        }
    }
}
