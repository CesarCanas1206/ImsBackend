<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionHistory extends Model
{
    use HasFactory, Uuids;

    protected $table = 'collection_history';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'value' => 'array',
    ];

    protected $fillable = [
        'collection_id',
        'form_id',
        'reference',
        'value',
        'sub_section',
        'created_by',
        'updated_by',
    ];
}
