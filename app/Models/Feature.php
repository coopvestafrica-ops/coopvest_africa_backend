<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feature extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_enabled',
        'category',
        'platforms',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'platforms' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get all logs for this feature
     */
    public function logs()
    {
        return $this->hasMany(FeatureLog::class);
    }

    /**
     * Check if feature is enabled for a specific platform
     */
    public function isEnabledForPlatform(string $platform): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        return in_array($platform, $this->platforms ?? ['web', 'mobile']);
    }

    /**
     * Enable the feature
     */
    public function enable(int $adminId = null): void
    {
        $this->update(['is_enabled' => true]);
        
        FeatureLog::create([
            'feature_id' => $this->id,
            'admin_id' => $adminId,
            'action' => 'enabled',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Disable the feature
     */
    public function disable(int $adminId = null): void
    {
        $this->update(['is_enabled' => false]);
        
        FeatureLog::create([
            'feature_id' => $this->id,
            'admin_id' => $adminId,
            'action' => 'disabled',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Toggle the feature
     */
    public function toggle(int $adminId = null): bool
    {
        $newState = !$this->is_enabled;
        $this->update(['is_enabled' => $newState]);
        
        FeatureLog::create([
            'feature_id' => $this->id,
            'admin_id' => $adminId,
            'action' => $newState ? 'enabled' : 'disabled',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $newState;
    }

    /**
     * Scope: Get only enabled features
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: Get only disabled features
     */
    public function scopeDisabled($query)
    {
        return $query->where('is_enabled', false);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->whereJsonContains('platforms', $platform);
    }
}
