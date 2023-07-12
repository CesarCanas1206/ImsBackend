<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'role';

    public function user()
    {
        return $this->belongsTo(user);
    }

    protected $fillable = [
        'name',
        'permissions',
        'access_level',
        'enabled',
        'default_page_id',
    ];
}
