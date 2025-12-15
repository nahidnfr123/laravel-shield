<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use NahidFerdous\Shield\Http\Requests\AssignPermissionToRoleRequest;
use NahidFerdous\Shield\Http\Requests\AssignPermissionToUserRequest;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Traits\ApiResponseTrait;
use Spatie\Permission\Models\Permission;

class PermissionController implements HasMiddleware
{
    use ApiResponseTrait;

    public static function middleware(): array
    {
        $model = 'permission';

        return [
            'auth',
            new Middleware(["shield.permission:view_$model"], only: ['index']),
            new Middleware(["shield.permission:show_$model"], only: ['show']),
            new Middleware(["shield.permission:create_$model"], only: ['store']),
            new Middleware(["shield.permission:update_$model"], only: ['update']),
            new Middleware(["shield.permission:delete_$model"], only: ['destroy']),
        ];
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        $permissions = Permission::get(['id', 'name', 'type'])->groupBy('type');

        if (request()->has('role')) {
            $role = Role::findBySlug(request('role'));
            $permissions = $permissions->map(function ($permission) use ($role) {
                $permission->map(function ($p) use ($role) {
                    $p->setAttribute('assigned', $role->hasPermissionTo($p->name));
                });

                return $permission;
            });
        }

        return $this->success('Success.', $permissions);
    }

    public function assignPermissionToRole(AssignPermissionToRoleRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        $permissions = $data['permissions'] ?? [];
        $role = Role::findBySlug(request('role'));
        $permissionNames = Permission::whereIn('id', $permissions)->pluck('name')->toArray();
        $role->syncPermissions($permissionNames);

        return response()->json([
            'message' => 'Permissions assigned to role successfully.',
        ]);
    }

    public function assignPermissionToUser(AssignPermissionToUserRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        $guard = $data['guard'];
        $userId = $data['user_id'];
        $permissions = $data['permissions'] ?? [];

        // Resolve which model belongs to the selected guard
        $provider = config("auth.guards.$guard.provider");

        if (! $provider) {
            return response()->json([
                'message' => "Invalid guard: $guard",
            ], 422);
        }

        $modelClass = config("auth.providers.$provider.model");

        if (! class_exists($modelClass)) {
            return response()->json([
                'message' => "Model for guard [$guard] not found.",
            ], 422);
        }

        // Load the user/admin from the correct table
        $user = $modelClass::find($userId);

        if (! $user) {
            return response()->json([
                'message' => "No user found for guard [$guard] with this ID.",
            ], 404);
        }

        // Fetch only permissions belonging to that guard
        $permissionNames = Permission::where('guard_name', $guard)
            ->whereIn('id', $permissions)
            ->pluck('name')
            ->toArray();

        // Sync permissions
        $user->syncPermissions($permissionNames);

        return response()->json([
            'message' => "Permissions assigned to $guard user successfully.",
        ]);
    }
}
