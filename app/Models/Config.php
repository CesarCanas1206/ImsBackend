<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasFactory, Uuids;

    protected $table = 'config';

    protected $fillable = [
        'id',
        'name',
        'code',
        'value',
        'type',
        'order',
        'public',
        'category',
    ];
}
