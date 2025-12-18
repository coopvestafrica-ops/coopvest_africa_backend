# Feature Flag Setup Guide - Laravel Backend

## Installation Steps

### 1. Register Middleware

Add the middleware to `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ... other middleware
    'feature-flag' => \App\Http\Middleware\CheckFeatureFlag::class,
];
```

### 2. Add Routes

Add these routes to `routes/api.php`:

```php
Route::middleware('auth:sanctum')->group(function () {
    // Feature flag routes
    Route::get('/features/enabled', [FeatureFlagController::class, 'getEnabledFeatures']);
    Route::get('/features/{featureName}/check', [FeatureFlagController::class, 'checkFeature']);
    Route::get('/features/{featureName}/config', [FeatureFlagController::class, 'getFeatureConfig']);
    Route::post('/features/multiple', [FeatureFlagController::class, 'getMultipleFeatures']);
    Route::post('/features/cache/clear', [FeatureFlagController::class, 'clearCache'])->middleware('admin');
    
    // Protected routes with feature flag middleware
    Route::middleware('feature-flag:new_payment_system')->group(function () {
        // Routes that require new_payment_system feature
        Route::post('/payments', [PaymentController::class, 'store']);
    });
});
```

### 3. Configure Environment

Add to `.env`:

```env
ADMIN_DASHBOARD_URL=http://localhost:3000
FEATURE_FLAG_CACHE_EXPIRATION=3600
```

### 4. Register Service Provider (Optional)

Create a service provider to auto-register the service:

```php
// app/Providers/FeatureFlagServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FeatureService;

class FeatureFlagServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(FeatureService::class, function ($app) {
            return new FeatureService();
        });
    }
}
```

Then add to `config/app.php` providers array:
```php
'providers' => [
    // ...
    App\Providers\FeatureFlagServiceProvider::class,
],
```

## Usage Examples

### 1. Check Feature in Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\FeatureService;

class PaymentController extends Controller
{
    public function __construct(private FeatureService $featureService)
    {
    }

    public function store(Request $request)
    {
        $userId = auth()->id();

        // Check if new payment system is enabled
        if ($this->featureService->isFeatureEnabled('new_payment_system', $userId)) {
            return $this->processNewPayment($request);
        }

        return $this->processOldPayment($request);
    }

    private function processNewPayment($request)
    {
        // New payment logic
    }

    private function processOldPayment($request)
    {
        // Old payment logic
    }
}
```

### 2. Use Middleware to Protect Routes

```php
// routes/api.php
Route::middleware('auth:sanctum', 'feature-flag:new_payment_system')
    ->post('/payments', [PaymentController::class, 'store']);
```

### 3. Get Feature Configuration

```php
$config = $this->featureService->getFeatureConfig('new_payment_system');

$timeout = $config['timeout'] ?? 30000;
$gateways = $config['paymentGateways'] ?? [];
```

### 4. Get All Enabled Features

```php
$userId = auth()->id();
$features = $this->featureService->getEnabledFeatures('web', $userId);

// Returns array of enabled feature names
```

### 5. Clear Cache

```php
// Clear specific feature cache
$this->featureService->clearCache('new_payment_system', 'web');

// Clear all cache
$this->featureService->clearCache();
```

## API Endpoints

### Get Enabled Features
```
GET /api/features/enabled?platform=web
Authorization: Bearer {token}

Response:
{
  "features": [
    {
      "name": "new_payment_system",
      "enabled": true,
      "config": {...}
    }
  ],
  "platform": "web",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

### Check Single Feature
```
GET /api/features/{featureName}/check?platform=web
Authorization: Bearer {token}

Response:
{
  "feature": "new_payment_system",
  "enabled": true,
  "config": {...},
  "platform": "web"
}
```

### Get Feature Configuration
```
GET /api/features/{featureName}/config?platform=web
Authorization: Bearer {token}

Response:
{
  "feature": "new_payment_system",
  "config": {
    "timeout": 30000,
    "paymentGateways": ["stripe", "paypal"]
  },
  "platform": "web"
}
```

### Get Multiple Features
```
POST /api/features/multiple?platform=web
Authorization: Bearer {token}

Body:
{
  "features": ["new_payment_system", "advanced_analytics"]
}

Response:
{
  "features": {
    "new_payment_system": {
      "enabled": true,
      "config": {...}
    },
    "advanced_analytics": {
      "enabled": false,
      "config": {}
    }
  },
  "platform": "web"
}
```

### Clear Cache
```
POST /api/features/cache/clear?feature=new_payment_system&platform=web
Authorization: Bearer {token}

Response:
{
  "message": "Cache cleared successfully",
  "feature": "new_payment_system",
  "platform": "web"
}
```

## Caching Strategy

- Features are cached for 1 hour (configurable via `FEATURE_FLAG_CACHE_EXPIRATION`)
- Cache key format: `feature:{featureName}:{platform}`
- Cache is automatically invalidated when:
  - Feature is toggled in admin dashboard
  - Feature configuration is updated
  - Cache clear endpoint is called
  - Manual cache clear is triggered

## Performance Considerations

1. **First Request**: Fetches from admin dashboard and caches
2. **Subsequent Requests**: Served from cache (within 1 hour)
3. **Cache Expiration**: After 1 hour, next request fetches fresh data
4. **Fallback**: If admin dashboard is unreachable, cached data is used
5. **Timeout**: HTTP requests timeout after 5 seconds

## Troubleshooting

### Features Not Loading
1. Check `ADMIN_DASHBOARD_URL` is correct
2. Verify admin dashboard is running
3. Check network connectivity
4. Review Laravel logs: `storage/logs/laravel.log`

### Cache Issues
1. Clear cache: `php artisan cache:clear`
2. Or use API endpoint: `POST /api/features/cache/clear`

### Authentication Issues
1. Ensure user is authenticated
2. Check token validity
3. Verify middleware order in routes

## Best Practices

1. **Feature Naming**: Use snake_case (e.g., `new_payment_system`)
2. **Caching**: Leverage caching for performance
3. **Fallback**: Always have fallback logic for disabled features
4. **Logging**: Monitor feature flag checks in logs
5. **Testing**: Test both enabled and disabled states
6. **Documentation**: Document feature flags in your team wiki

## Integration with Admin Dashboard

The feature flags are managed from the admin dashboard at:
- URL: `http://localhost:3000/admin/features`
- Create, update, toggle, and monitor features
- Set rollout percentages
- Target specific user segments
- Schedule feature availability

