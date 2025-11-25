<?php

    namespace NahidFerdous\Shield\Tests\Fixtures;

use NahidFerdous\Shield\Concerns\HasShieldRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasShieldRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'suspended_at',
        'suspension_reason',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'suspended_at' => 'datetime',
    ];
}
