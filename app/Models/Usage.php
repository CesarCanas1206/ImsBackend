<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Usage extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'usage';
    protected $with = ['fields', 'asset'];

    protected $fillable = [
        'id',
        'parent_id',
        'form_id',
        'asset_id',
        'name',
        'date',
        'end_date',
        'start',
        'end',
        'title',
    ];

    public function fields()
    {
        return $this->hasMany(\App\Models\CollectionField::class, 'collection_id', 'id')
            ->select(['id', 'collection_id', 'sub_section', 'reference', 'value']);
    }

    public function asset()
    {
        return $this->hasOne(\App\Models\Asset::class, 'id', 'asset_id');
    }

    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class, 'id', 'parent_id');
    }
}
