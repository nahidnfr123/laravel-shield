<?php

namespace HasinHayder\Tyro\Http\Controllers;

use HasinHayder\Tyro\Models\Privilege;
use HasinHayder\Tyro\Models\Role;
use HasinHayder\Tyro\Support\TyroCache;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class RolePrivilegeController extends Controller
{
    public function index(Role $role)
    {
        return $role->load('privileges');
    }

    public function store(Request $request, Role $role)
    {
        $data = $request->validate([
            'privilege_id' => [
                'required',
                'integer',
                Rule::exists(config('tyro.tables.privileges', 'privileges'), 'id'),
            ],
        ]);

        $privilege = Privilege::findOrFail($data['privilege_id']);
        $role->privileges()->syncWithoutDetaching($privilege);
        TyroCache::forgetUsersByRole($role);

        return $role->load('privileges');
    }

    public function destroy(Role $role, Privilege $privilege)
    {
        $role->privileges()->detach($privilege);
        TyroCache::forgetUsersByRole($role);

        return $role->load('privileges');
    }
}
