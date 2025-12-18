<?php

namespace App\Helpers;

use App\Models\Feature;
use Illuminate\Support\Facades\Cache;

class FeatureFlag
{
    /**
     * Check if a feature is enabled
     */
    public static function isEnabled(string $slug, string $platform = 'web'): bool
    {
        $cacheKey = "feature:{$slug}:{$platform}";

        return Cache::remember($cacheKey, 3600, function () use ($slug, $platform) {
            $feature = Feature::where('slug', $slug)->first();

            if (!$feature) {
                return false;
            }

            return $feature->isEnabledForPlatform($platform);
        });
    }

    /**
     * Get a feature by slug
     */
    public static function get(string $slug): ?Feature
    {
        return Cache::remember("feature:model:{$slug}", 3600, function () use ($slug) {
            return Feature::where('slug', $slug)->first();
        });
    }

    /**
     * Get all enabled features for a platform
     */
    public static function getEnabledForPlatform(string $platform = 'web'): array
    {
        $cacheKey = "features:enabled:{$platform}";

        return Cache::remember($cacheKey, 3600, function () use ($platform) {
            return Feature::enabled()
                ->byPlatform($platform)
                ->pluck('slug')
                ->toArray();
        });
    }

    /**
     * Clear feature cache
     */
    public static function clearCache(string $slug = null): void
    {
        if ($slug) {
            Cache::forget("feature:{$slug}:web");
            Cache::forget("feature:{$slug}:mobile");
            Cache::forget("feature:model:{$slug}");
        } else {
            Cache::flush();
        }
    }

    /**
     * Get feature status with metadata
     */
    public static function getStatus(string $slug, string $platform = 'web'): array
    {
        $feature = self::get($slug);

        if (!$feature) {
            return [
                'slug' => $slug,
                'is_enabled' => false,
                'platform' => $platform,
                'metadata' => null,
            ];
        }

        return [
            'slug' => $slug,
            'is_enabled' => $feature->isEnabledForPlatform($platform),
            'platform' => $platform,
            'metadata' => $feature->metadata,
            'category' => $feature->category,
        ];
    }
}
