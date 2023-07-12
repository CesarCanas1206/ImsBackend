<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'asset';
    protected $with = ['fields', 'parent'];

    protected $fillable = [
        'id',
        'name',
        'parent_id',
    ];

    public function fields()
    {
        return $this->hasMany(\App\Models\CollectionField::class, 'collection_id', 'id')
            ->select(['id', 'collection_id', 'sub_section', 'reference', 'value']);
    }

    public function parent()
    {
        return $this->hasOne(\App\Models\Asset::class, 'id', 'parent_id')
            ->select(['id', 'parent_id', 'name']);
    }

    public function assets()
    {
        $results = $this->hasMany(\App\Models\Asset::class, 'parent_id', 'id');

        if (!empty(request()->user()->id) && request()->user()->id) {
            $hasLinks = UserAsset::where('user_id', request()->user()->id)->count();
            if (!empty($hasLinks)) {
                $results = $results->join('user_asset', 'user_asset.asset_id', '=', 'asset.id')
                    ->where('user_asset.user_id', request()->user()->id);
                $results = $results->select('asset.*');
            }
        }

        return $results;
    }

    public function assetForms()
    {
        return $this->hasMany(\App\Models\AssetForm::class, 'asset_id', 'id');
    }
}
