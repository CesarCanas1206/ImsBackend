<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory, Uuids;

    protected $table = 'file';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'path',
        'name',
    ];
}
