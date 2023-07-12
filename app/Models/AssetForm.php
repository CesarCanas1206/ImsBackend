<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetForm extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'asset_form';
    protected $with = ['asset', 'form'];

    protected $fillable = [
        'id',
        'asset_id',
        'form_id',
    ];

    public function asset()
    {
        return $this->hasOne(Asset::class, 'id', 'asset_id')
            ->select(['id', 'parent_id', 'name']);
    }

    public function form()
    {
        return $this->hasOne(Form::class, 'id', 'form_id')
            ->select(['id', 'parent_id', 'name', 'category', 'props', 'endpoint', 'reminders', 'custom', 'reference']);
    }
}
