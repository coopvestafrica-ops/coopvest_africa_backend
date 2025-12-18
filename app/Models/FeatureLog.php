<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureLog extends Model
{
    protected $fillable = [
        'feature_id',
        'admin_id',
        'action',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public $timestamps = true;
    public const UPDATED_AT = null;

    /**
     * Get the feature
     */
    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    /**
     * Get the admin who made the change
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
