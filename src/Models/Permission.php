<?php

namespace NahidFerdous\Shield\Models;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($role) {
            if (empty($role->slug) && ! empty($role->name)) {
                $role->slug = Str::slug($role->name);
            }
        });

        static::updating(function ($role) {
            if ($role->isDirty('name')) {
                $role->slug = Str::slug($role->name);
            }
        });
    }
}
