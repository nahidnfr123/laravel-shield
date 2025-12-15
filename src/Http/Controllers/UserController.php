<?php

namespace NahidFerdous\Shield\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use NahidFerdous\Shield\Http\Requests\ShieldUserCreateRequest;
use NahidFerdous\Shield\Http\Requests\ShieldUserUpdateRequest;
use NahidFerdous\Shield\Models\Role;
use NahidFerdous\Shield\Support\Policy;
use NahidFerdous\Shield\Traits\ApiResponseTrait;
use NahidFerdous\Shield\Traits\HandlesPagination;

class UserController implements HasMiddleware
{
    use ApiResponseTrait, HandlesPagination;

    public static function middleware(): array
    {
        $model = 'user';
        $userClass = resolveAuthenticatableClass();

        return [
            'auth',
            new Middleware(["shield.permission:view_$model"], only: ['index']),
            //            new Middleware(["shield.permission:create_$model"], only: ['store']),
            new Middleware([Policy::canView($userClass, 'user', 'id')], only: ['show']),
            new Middleware([Policy::canUpdate($userClass, 'user', 'id')], only: ['update']),
            new Middleware([Policy::canDelete($userClass, 'user', 'id')], only: ['destroy']),
            //            new Middleware(["permission_or_self:show_$model"], only: ['show']),
            //            new Middleware(["permission_or_self:update_$model"], only: ['update']),
            //            new Middleware(["permission:delete_$model"], only: ['destroy']),
        ];
    }

    /**
     * Get all users
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $simple = request('simple', true);
        $users = $this->userQuery()->with('roles:name');
        $users = $this->paginateIfRequested($users, $simple);
        $users->each(fn($u) => $u->roles->makeHidden('pivot'));

        return $this->success('User list', $this->formatePaginatedData($users, $simple));
    }

    /**
     * Create a new user
     */
    public function store(ShieldUserCreateRequest $request): \Illuminate\Http\JsonResponse
    {
        $userClass = $this->userClass();
        $model = new $userClass;
        // Use validated() if it's NOT the default CreateUserRequest
        if (get_class($request) !== ShieldUserCreateRequest::class) {
            $validatedData = $request->validated();
            $userData = array_intersect_key($validatedData, array_flip($model->getFillable()));
        } else {
            $userData = $request->only($model->getFillable());
        }

        $user = shield()->register($userData);

        return $this->success('User created successfully', $user);
    }

    /**
     * Show a specific user
     */
    public function show($user): \Illuminate\Http\JsonResponse
    {
        return $this->success('User details', $this->resolveUser($user));
    }

    /**
     * Update a user
     */
    public function update(ShieldUserUpdateRequest $request, $user): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $user = $this->resolveUser($user);
        $loggedInUser = $request->user();

        $user->fill(array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'password' => isset($data['password']) && $data['password'] ? Hash::make($data['password']) : null,
            'email_verified_at' => $data['email_verified_at'] ?? null,
        ]));

        if ($loggedInUser->id !== $user->id && !$loggedInUser->tokenCan('admin') && !$loggedInUser->tokenCan('super-admin')) {
            throw new MissingAbilityException('Not Authorized');
        }

        $user->save();

        if (!empty($data['roles']) || !empty($data['role'])) {
            $roles = is_array($data['roles'] ?? $data['role']) ? ($data['roles'] ?? $data['role']) : [$data['roles'] ?? $data['role']];
            $existingRoles = Role::whereIn('id', $roles)->pluck('id')->toArray();
            if ($existingRoles) {
                $user->roles()->sync($existingRoles);
            }
        }

        return $this->success('User updated successfully.', $user);
    }

    /**
     * Delete a user
     */
    public function destroy($user): \Illuminate\Http\JsonResponse
    {
        $user = $this->resolveUser($user);
        $adminRole = Role::where('slug', 'admin')->first();

        if ($adminRole && $user->roles->contains($adminRole)) {
            $adminCount = $adminRole->users()->count();
            if ($adminCount === 1) {
                return $this->failure('Create another admin before deleting this only admin user', 409);
            }
        }

        $user->delete();

        return $this->success('User deleted successfully.');
    }

    /**
     * Change password for an authenticated user
     */
    public function changePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'current_password' => ['Current password is incorrect'],
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return $this->success('Password updated successfully');
    }

    /**
     * Get the configured user class
     */
    protected function userClass(): string
    {
        return resolveAuthenticatableClass();
    }

    /**
     * Get a query builder for the user model
     */
    protected function userQuery()
    {
        $class = $this->userClass();

        return $class::query();
    }

    /**
     * Resolve user from ID or instance
     */
    protected function resolveUser($user): \Illuminate\Contracts\Auth\Authenticatable
    {
        if ($user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            return $user;
        }

        return $this->userQuery()->findOrFail($user);
    }
}
