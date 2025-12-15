<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use NahidFerdous\Shield\Http\Requests\RoleRequest;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Traits\ApiResponseTrait;
use NahidFerdous\Shield\Traits\HandlesPagination;

class RoleController implements HasMiddleware
{
    use ApiResponseTrait, HandlesPagination;

    public static function middleware(): array
    {
        $model = 'role';

        return [
            'auth',
            new Middleware(["shield.permission:view_$model"], only: ['index']),
            new Middleware(["shield.permission:show_$model"], only: ['show']),
            new Middleware(["shield.permission:create_$model"], only: ['store']),
            new Middleware(["shield.permission:update_$model"], only: ['update']),
            new Middleware(["shield.permission:delete_$model"], only: ['destroy']),
        ];
    }

    public function index(): JsonResponse
    {
        $simple = request('simple', true);
        $roles = Role::withCount(['permissions', 'users'])
            ->latest()
            ->orderBy(request('order_by', 'name'), request('order', 'ASC'));
        $roles = $this->paginateIfRequested($roles, $simple);

        return $this->success('Roles retrieved successfully', $this->formatePaginatedData($roles, $simple));
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['guard_name'] = $data['guard_name'] ?? 'web';

        if (Role::where('name', $data['name'])
            ->when(! empty($data['guard_name']), function ($query) use ($data) {
                return $query->where('guard_name', $data['guard_name']);
            })
            ->exists()) {
            return $this->failure('Role already exists', 409);
        }

        return $this->success('Roles retrieved successfully', Role::create($data));

    }

    public function show(Role $role): JsonResponse
    {
        $role->loadCount(['permissions', 'users']);

        return $this->success('Role retrieved successfully', $role);
    }

    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        $data = $request->validated();
        $data['guard_name'] = $data['guard_name'] ?? 'web';
        $role->update($data);

        return $this->success('Role updated successfully', $role);
    }

    public function destroy(Role $role)
    {
        $protected = config('shield.protected_role_slugs', ['admin', 'super-admin']);
        if (in_array($role->slug, $protected, true)) {
            return response(['error' => 1, 'message' => 'you cannot delete this role'], 422);
        }

        $role->delete();

        return $this->success('Role has been deleted', $role);
    }
}
