<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HirerUser extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'hirer_user';
    protected $with = ['hirer', 'user'];

    protected $fillable = [
        'id',
        'hirer_id',
        'user_id',
    ];

    public function hirer()
    {
        return $this->belongsTo(Hirer::class, 'hirer_id', 'id')->without('fields')
            ->select(['id', 'parent_id', 'name']);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->with('fields')
            ->select(['id', 'name', 'first_name', 'last_name', 'email']);
    }
}
