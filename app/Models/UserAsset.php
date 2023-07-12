<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAsset extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'user_asset';
    protected $with = ['user', 'asset'];

    protected $fillable = [
        'id',
        'user_id',
        'asset_id',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')
            ->select(['id', 'name', 'email']);
    }

    public function asset()
    {
        return $this->hasOne(Asset::class, 'id', 'asset_id')
            ->select(['id', 'parent_id', 'name']);

    }
}
