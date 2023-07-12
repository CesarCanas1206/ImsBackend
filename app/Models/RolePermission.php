<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RolePermission extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'role_permission';
    protected $with = ['role', 'permission'];

    protected $fillable = [
        'id',
        'role_id',
        'permission_id',
    ];

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id')
            ->select(['id', 'name', 'access_level']);
    }

    public function permission()
    {
        return $this->hasOne(Permission::class, 'id', 'permission_id')
            ->select(['id', 'name', 'code']);
    }
}
