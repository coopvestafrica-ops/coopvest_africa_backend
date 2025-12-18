<?php

namespace App\Http\Controllers;

use App\Services\FeatureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Feature Flag Controller for managing feature flags
 */
class FeatureFlagController extends Controller
{
    public function __construct(private FeatureService $featureService)
    {
    }

    /**
     * Get all enabled features for current user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEnabledFeatures(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $platform = $request->query('platform', 'web');

            $features = $this->featureService->getEnabledFeatures($platform, $userId);

            return response()->json([
                'features' => $features,
                'platform' => $platform,
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch enabled features',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if specific feature is enabled
     *
     * @param Request $request
     * @param string $featureName
     * @return JsonResponse
     */
    public function checkFeature(Request $request, $featureName): JsonResponse
    {
        try {
            $userId = auth()->id();
            $platform = $request->query('platform', 'web');

            $enabled = $this->featureService->isFeatureEnabled($featureName, $userId, $platform);
            $config = $this->featureService->getFeatureConfig($featureName, $platform);

            return response()->json([
                'feature' => $featureName,
                'enabled' => $enabled,
                'config' => $config,
                'platform' => $platform,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check feature',
                'feature' => $featureName,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get feature configuration
     *
     * @param Request $request
     * @param string $featureName
     * @return JsonResponse
     */
    public function getFeatureConfig(Request $request, $featureName): JsonResponse
    {
        try {
            $platform = $request->query('platform', 'web');
            $config = $this->featureService->getFeatureConfig($featureName, $platform);

            return response()->json([
                'feature' => $featureName,
                'config' => $config,
                'platform' => $platform,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch feature configuration',
                'feature' => $featureName,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear feature cache
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $featureName = $request->query('feature');
            $platform = $request->query('platform');

            $this->featureService->clearCache($featureName, $platform);

            return response()->json([
                'message' => 'Cache cleared successfully',
                'feature' => $featureName,
                'platform' => $platform,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to clear cache',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get multiple features status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMultipleFeatures(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'features' => 'required|array',
                'features.*' => 'string',
                'platform' => 'string|in:web,mobile,admin_dashboard',
            ]);

            $userId = auth()->id();
            $platform = $request->query('platform', 'web');
            $featureNames = $request->input('features', []);

            $features = [];
            foreach ($featureNames as $featureName) {
                $enabled = $this->featureService->isFeatureEnabled($featureName, $userId, $platform);
                $config = $this->featureService->getFeatureConfig($featureName, $platform);

                $features[$featureName] = [
                    'enabled' => $enabled,
                    'config' => $config,
                ];
            }

            return response()->json([
                'features' => $features,
                'platform' => $platform,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch multiple features',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
