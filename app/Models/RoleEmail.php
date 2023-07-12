<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleEmail extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'role_email';
    protected $with = ['role'];

    protected $fillable = [
        'id',
        'role_id',
        'email_id',
    ];

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id')
            ->select(['id', 'name', 'access_level']);
    }

    // public function email()
    // {
    //     return $this->hasOne(Email::class, 'id', 'email_id');
    // }
}
