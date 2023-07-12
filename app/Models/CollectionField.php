<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CollectionField extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'collection_field';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'collection_id',
        'form_id',
        'reference',
        'value',
        'sub_section',
    ];

    public function getTable()
    {
        $header = request()->header('x-collection-ref') ?? $_SERVER['x-collection-ref'] ?? 'collection';
        return $header . '_field';
    }

    public function collection()
    {
        return $this->belongsTo(\App\Models\Collection::class);
    }
}
