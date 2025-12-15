<?php

namespace NahidFerdous\Shield\Models;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected static function boot()
    {
        $protected = config('shield.protected_role_slugs', ['admin', 'super-admin']);
        parent::boot();

        static::creating(function ($role) use ($protected) {
            if (empty($role->slug) && ! empty($role->name) && ! in_array($role->slug, $protected, true)) {
                $role->slug = Str::slug($role->name).'-'.$role->guard_name;
            }
        });

        static::updating(function ($role) use ($protected) {
            if ($role->isDirty('name') && ! in_array($role->slug, $protected, true)) {
                $role->slug = Str::slug($role->name).'-'.$role->guard_name;
            }
        });
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->morphedByMany(
            config('auth.providers.users.model'),
            'model',
            'model_has_roles',
            'role_id',
            'model_id'
        );
    }
}
