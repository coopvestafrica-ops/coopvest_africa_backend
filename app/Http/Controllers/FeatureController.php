<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\FeatureLog;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeatureController extends Controller
{
    /**
     * Get all features
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Feature::query();

            // Filter by status
            if ($request->has('status')) {
                if ($request->status === 'enabled') {
                    $query->enabled();
                } elseif ($request->status === 'disabled') {
                    $query->disabled();
                }
            }

            // Filter by category
            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            // Filter by platform
            if ($request->has('platform')) {
                $query->byPlatform($request->platform);
            }

            // Search by name or slug
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            }

            $features = $query->paginate($request->get('per_page', 15));

            return ApiResponse::success('Features retrieved successfully', $features);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve features', 500, $e->getMessage());
        }
    }

    /**
     * Get a single feature
     */
    public function show(Feature $feature): JsonResponse
    {
        try {
            $feature->load('logs');
            return ApiResponse::success('Feature retrieved successfully', $feature);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve feature', 500, $e->getMessage());
        }
    }

    /**
     * Create a new feature
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:features',
                'slug' => 'required|string|unique:features',
                'description' => 'nullable|string',
                'category' => 'required|string',
                'platforms' => 'required|array|min:1',
                'platforms.*' => 'in:web,mobile',
                'metadata' => 'nullable|array',
            ]);

            $feature = Feature::create($validated);

            return ApiResponse::success('Feature created successfully', $feature, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create feature', 500, $e->getMessage());
        }
    }

    /**
     * Update a feature
     */
    public function update(Request $request, Feature $feature): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|unique:features,name,' . $feature->id,
                'slug' => 'sometimes|string|unique:features,slug,' . $feature->id,
                'description' => 'nullable|string',
                'category' => 'sometimes|string',
                'platforms' => 'sometimes|array|min:1',
                'platforms.*' => 'in:web,mobile',
                'metadata' => 'nullable|array',
            ]);

            $changes = [];
            foreach ($validated as $key => $value) {
                if ($feature->$key !== $value) {
                    $changes[$key] = [
                        'old' => $feature->$key,
                        'new' => $value,
                    ];
                }
            }

            $feature->update($validated);

            if (!empty($changes)) {
                FeatureLog::create([
                    'feature_id' => $feature->id,
                    'admin_id' => auth()->id(),
                    'action' => 'updated',
                    'changes' => $changes,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }

            return ApiResponse::success('Feature updated successfully', $feature);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update feature', 500, $e->getMessage());
        }
    }

    /**
     * Delete a feature
     */
    public function destroy(Feature $feature): JsonResponse
    {
        try {
            $feature->delete();
            return ApiResponse::success('Feature deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete feature', 500, $e->getMessage());
        }
    }

    /**
     * Enable a feature
     */
    public function enable(Feature $feature): JsonResponse
    {
        try {
            $feature->enable(auth()->id());
            return ApiResponse::success('Feature enabled successfully', $feature);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to enable feature', 500, $e->getMessage());
        }
    }

    /**
     * Disable a feature
     */
    public function disable(Feature $feature): JsonResponse
    {
        try {
            $feature->disable(auth()->id());
            return ApiResponse::success('Feature disabled successfully', $feature);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to disable feature', 500, $e->getMessage());
        }
    }

    /**
     * Toggle a feature
     */
    public function toggle(Feature $feature): JsonResponse
    {
        try {
            $newState = $feature->toggle(auth()->id());
            return ApiResponse::success(
                'Feature ' . ($newState ? 'enabled' : 'disabled') . ' successfully',
                $feature
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to toggle feature', 500, $e->getMessage());
        }
    }

    /**
     * Get feature logs
     */
    public function logs(Feature $feature, Request $request): JsonResponse
    {
        try {
            $logs = $feature->logs()
                ->with('admin')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return ApiResponse::success('Feature logs retrieved successfully', $logs);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve feature logs', 500, $e->getMessage());
        }
    }

    /**
     * Get features for a specific platform
     */
    public function byPlatform(string $platform): JsonResponse
    {
        try {
            $features = Feature::byPlatform($platform)->get();
            return ApiResponse::success('Features retrieved successfully', $features);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve features', 500, $e->getMessage());
        }
    }

    /**
     * Check if a feature is enabled
     */
    public function isEnabled(string $slug, Request $request): JsonResponse
    {
        try {
            $feature = Feature::where('slug', $slug)->first();

            if (!$feature) {
                return ApiResponse::error('Feature not found', 404);
            }

            $platform = $request->get('platform', 'web');
            $isEnabled = $feature->isEnabledForPlatform($platform);

            return ApiResponse::success('Feature status retrieved', [
                'slug' => $slug,
                'is_enabled' => $isEnabled,
                'platform' => $platform,
                'feature' => $feature,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to check feature status', 500, $e->getMessage());
        }
    }
}
