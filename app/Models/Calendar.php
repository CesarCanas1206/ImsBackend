<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Calendar extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'calendar';

    protected $fillable = [
        'id',
        'form_id',
        'parent_id',
        'asset_id',
        'usage_id',
        'pending',
        'allow',
        'title',
        'start',
        'end',
        'slug',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
