<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FeatureService;

/**
 * Middleware to check if a feature flag is enabled
 * Usage: Route::middleware('feature-flag:feature_name')->group(...)
 */
class CheckFeatureFlag
{
    public function __construct(private FeatureService $featureService)
    {
    }

    public function handle(Request $request, Closure $next, $featureName)
    {
        $userId = auth()->id();

        if (!$this->featureService->isFeatureEnabled($featureName, $userId, 'web')) {
            return response()->json([
                'error' => 'Feature not available',
                'feature' => $featureName,
            ], 403);
        }

        return $next($request);
    }
}
