<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserEmail extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'user_email';
    protected $with = ['user'];

    protected $fillable = [
        'id',
        'user_id',
        'email_id',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')
            ->select(['id', 'name', 'email']);
    }

    // public function email()
    // {
    //     return $this->hasOne(Email::class, 'id', 'email_id');
    // }
}
