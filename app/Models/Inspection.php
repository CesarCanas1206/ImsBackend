<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'inspection';

    protected $fillable = [
        'id',
        'application_id',
        'form_id',
        'asset_id',
    ];

    public function fields()
    {
        return $this->hasMany(\App\Models\CollectionField::class, 'collection_id', 'id')
            ->select(['id', 'collection_id', 'sub_section', 'reference', 'value']);
    }
}
