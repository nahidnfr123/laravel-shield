<?php

namespace NahidFerdous\Shield\Support;

class Policy
{
    /**
     * Generate "permission_or_self" middleware for updating a model.
     *
     * @param  string  $modelClass  e.g. Post::class
     * @param  string|null  $routeParam  route parameter name (default: model name)
     * @param  string  $ownerAttribute  owner column (default: created_by)
     */
    public static function canUpdate(string $modelClass, ?string $routeParam = null, string $ownerAttribute = 'created_by'): string
    {
        // turn "App\Models\Post" → "post"
        $model = strtolower(class_basename($modelClass));
        // default route param: post, user, etc.
        $routeParam ??= $model;
        // permission name example: update-post
        $permission = "update_$model";

        return "permission_or_self:$permission,$routeParam,$ownerAttribute";
    }

    public static function canView(string $modelClass, ?string $routeParam = null, string $ownerAttribute = 'created_by'): string
    {
        $model = strtolower(class_basename($modelClass));
        $routeParam ??= $model;

        return "permission_or_self:view_$model,$routeParam,$ownerAttribute";
    }

    public static function canDelete(string $modelClass, ?string $routeParam = null, string $ownerAttribute = 'created_by'): string
    {
        $model = strtolower(class_basename($modelClass));
        $routeParam ??= $model;

        return "permission_or_self:delete_$model,$routeParam,$ownerAttribute";
    }
}
