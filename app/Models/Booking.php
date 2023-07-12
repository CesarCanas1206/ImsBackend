<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'booking';
    protected $with = ['fields', 'hirer', 'usage'];

    protected $fillable = [
        'id',
        'name',
        'hirer_id',
        'type',
        'parent_id',
        'user_id',
        'form_id',
    ];

    public function usage()
    {
        return $this->hasMany(\App\Models\Usage::class, 'parent_id', 'id');
        // ->select(['id', 'collection_id', 'sub_section', 'reference', 'value']);
    }

    public function fields()
    {
        return $this->hasMany(\App\Models\CollectionField::class, 'collection_id', 'id')
            ->select(['id', 'collection_id', 'sub_section', 'reference', 'value']);
    }

    public function hirer()
    {
        return $this->hasOne(\App\Models\Hirer::class, 'id', 'hirer_id');
    }

    // public function season()
    // {
    //     return $this->hasOne(\App\Models\Collection::class, 'id', 'season_id')->where('slug', 'season');
    // }

    // public function hirer()
    // {
    //     return $this->belongsTo(\App\Models\Hirer::class, 'id', 'hirer_id')->with('user');
    // }
}
