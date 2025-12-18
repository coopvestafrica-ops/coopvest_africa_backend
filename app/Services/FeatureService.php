<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Feature Flag Service for managing feature flags from admin dashboard
 */
class FeatureService
{
    private $adminDashboardUrl;
    private $cacheExpiration = 3600; // 1 hour

    public function __construct()
    {
        $this->adminDashboardUrl = config('services.admin_dashboard.url', 'http://localhost:3000');
    }

    /**
     * Check if a feature is enabled for a user
     *
     * @param string $featureName
     * @param string|null $userId
     * @param string $platform
     * @return bool
     */
    public function isFeatureEnabled($featureName, $userId = null, $platform = 'web')
    {
        try {
            $feature = $this->getFeature($featureName, $platform);

            if (!$feature || !$feature['enabled']) {
                return false;
            }

            // Check rollout percentage
            if ($feature['rolloutPercentage'] < 100) {
                if (!$this->isUserInRollout($userId, $featureName, $feature['rolloutPercentage'])) {
                    return false;
                }
            }

            // Check target audience
            if (!$this->isUserInTargetAudience($userId, $feature)) {
                return false;
            }

            // Check date range
            if (!$this->isWithinDateRange($feature)) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error checking feature flag', [
                'feature' => $featureName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get feature configuration
     *
     * @param string $featureName
     * @param string $platform
     * @return array
     */
    public function getFeatureConfig($featureName, $platform = 'web')
    {
        try {
            $feature = $this->getFeature($featureName, $platform);
            return $feature['config'] ?? [];
        } catch (\Exception $e) {
            Log::error('Error getting feature config', [
                'feature' => $featureName,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get all enabled features for a platform
     *
     * @param string $platform
     * @param string|null $userId
     * @return array
     */
    public function getEnabledFeatures($platform = 'web', $userId = null)
    {
        $cacheKey = "features:{$platform}:{$userId}";

        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($platform, $userId) {
            try {
                $response = Http::timeout(5)->get("{$this->adminDashboardUrl}/api/features/platform/{$platform}");

                if ($response->successful()) {
                    $features = $response->json();
                    return array_filter($features, function ($feature) use ($userId) {
                        return $this->isFeatureEnabled($feature['name'], $userId);
                    });
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch features from admin dashboard', [
                    'error' => $e->getMessage(),
                ]);
            }

            return [];
        });
    }

    /**
     * Get feature from cache or admin dashboard
     *
     * @param string $featureName
     * @param string $platform
     * @return array|null
     */
    private function getFeature($featureName, $platform = 'web')
    {
        $cacheKey = "feature:{$featureName}:{$platform}";

        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($featureName, $platform) {
            try {
                $response = Http::timeout(5)->get("{$this->adminDashboardUrl}/api/features", [
                    'name' => $featureName,
                    'platform' => $platform,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $features = $data['features'] ?? [];
                    return $features[0] ?? null;
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch feature from admin dashboard', [
                    'feature' => $featureName,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        });
    }

    /**
     * Check if user is in rollout percentage
     *
     * @param string|null $userId
     * @param string $featureName
     * @param int $rolloutPercentage
     * @return bool
     */
    private function isUserInRollout($userId, $featureName, $rolloutPercentage)
    {
        if (!$userId) {
            return rand(0, 100) <= $rolloutPercentage;
        }

        // Consistent hashing for same user
        $hash = crc32($userId . $featureName) % 100;
        return $hash < $rolloutPercentage;
    }

    /**
     * Check if user is in target audience
     *
     * @param string|null $userId
     * @param array $feature
     * @return bool
     */
    private function isUserInTargetAudience($userId, $feature)
    {
        $targetAudience = $feature['targetAudience'] ?? 'all';

        if ($targetAudience === 'all') {
            return true;
        }

        if ($targetAudience === 'specific_regions' && !empty($feature['targetRegions'])) {
            // Implement region checking based on user data
            return true; // Placeholder
        }

        if ($targetAudience === 'specific_users' && !empty($feature['targetUserIds'])) {
            return in_array($userId, $feature['targetUserIds']);
        }

        return true;
    }

    /**
     * Check if feature is within date range
     *
     * @param array $feature
     * @return bool
     */
    private function isWithinDateRange($feature)
    {
        $now = now();

        if (!empty($feature['startDate'])) {
            $startDate = \Carbon\Carbon::parse($feature['startDate']);
            if ($now->isBefore($startDate)) {
                return false;
            }
        }

        if (!empty($feature['endDate'])) {
            $endDate = \Carbon\Carbon::parse($feature['endDate']);
            if ($now->isAfter($endDate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear feature cache
     *
     * @param string|null $featureName
     * @param string|null $platform
     * @return void
     */
    public function clearCache($featureName = null, $platform = null)
    {
        if ($featureName && $platform) {
            Cache::forget("feature:{$featureName}:{$platform}");
        } else {
            Cache::flush();
        }
    }

    /**
     * Get feature by ID from admin dashboard
     *
     * @param string $featureId
     * @return array|null
     */
    public function getFeatureById($featureId)
    {
        $cacheKey = "feature_id:{$featureId}";

        return Cache::remember($cacheKey, $this->cacheExpiration, function () use ($featureId) {
            try {
                $response = Http::timeout(5)->get("{$this->adminDashboardUrl}/api/features/{$featureId}");

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch feature by ID from admin dashboard', [
                    'feature_id' => $featureId,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        });
    }
}
