<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hirer extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'hirer';
    protected $with = ['fields'];

    protected $fillable = [
        'id',
        'name',
        'parent_id',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function fields()
    {
        return $this->hasMany(\App\Models\CollectionField::class, 'collection_id', 'id')
            ->select(['id', 'collection_id', 'sub_section', 'reference', 'value']);
    }

    // public function user()
    // {
    //     return $this->hasOne(\App\Models\User::class, 'id', 'user_id')
    //         ->select(['name', 'email', 'id']);
    // }

    public function hirerUsers()
    {
        return $this->hasMany(\App\Models\HirerUser::class, 'hirer_id', 'id');
    }

    public function allocation()
    {
        return $this->hasMany(\App\Models\Booking::class, 'hirer_id', 'id')->where('type', 'allocation');
        // ->where(['name', 'email']);
    }
}
