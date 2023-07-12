<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPermission extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'user_permission';
    protected $with = ['user', 'permission'];

    protected $fillable = [
        'id',
        'user_id',
        'permission_id',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')
            ->select(['id', 'name', 'email']);
    }

    public function permission()
    {
        return $this->hasOne(Permission::class, 'id', 'permission_id')
            ->select(['id', 'name', 'code']);
    }
}
