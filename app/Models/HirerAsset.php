<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HirerAsset extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'hirer_asset';
    protected $with = ['hirer', 'asset'];

    protected $fillable = [
        'id',
        'hirer_id',
        'asset_id',
    ];

    public function hirer()
    {
        return $this->belongsTo(Hirer::class, 'hirer_id', 'id')
            ->select(['id', 'parent_id', 'name']);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id', 'id')
            ->select(['id', 'parent_id', 'name']);
    }
}
