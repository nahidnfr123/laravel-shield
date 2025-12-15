<?php

namespace NahidFerdous\Shield\Database\Seeders;

class PermissionsData
{
    public function permissionGroups(): array
    {
        return [
            'user' => [
                ...$this->defaultPermissionsBasic('user'),
            ],
            'role' => [
                ...$this->defaultPermissionsBasic('role'),
            ],
            'permission' => [
                ...$this->defaultPermissionsBasic('permission'),
                ['view_permission', 0, ['web', 'api']],
                ['assign_permission', 0, ['web']],
            ],
            'cache' => [
                'clear_cache',
            ],
        ];
    }

    public function defaultPermissionsBasic($key): array
    {
        return [
            "view_$key",
            "show_$key",
            "create_$key",
            "update_$key",
            "delete_$key",
        ];
    }

    public function defaultPermissionsExtended($key): array
    {
        return [
            "view_$key",
            "show_$key",
            "create_$key",
            "update_$key",
            "delete_$key",
            "view_any_$key",
            "show_any_$key",
            "update_any_$key",
            "delete_any_$key",
        ];
    }

    public function trashedPermissions($key): array
    {
        return [
            "view_trashed_$key",
            "restore_$key",
            "force_delete_$key",
        ];
    }
}
