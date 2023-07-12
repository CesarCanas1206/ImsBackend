<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'collection_asset';

    protected $with = 'fields';

    protected $fillable = [
        'id',
        'form_id',
        'parent_id',
        'specific_id',
        'linked',
        'slug',
        // 'inspector_id',
        // 'completed',
        // 'completed_at',
    ];

    protected $hidden = [
        // 'completed',
        // 'completed_at',
        // 'archived',
        // 'reviewed',
        // 'review_date',
        // 'report_date',
        // 'linked',
        // 'inspector_id',
        // 'inspection_started',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function getTable()
    {
        return 'collection';
        // return request()->header('x-collection-ref') ?? $_SERVER['x-collection-ref'] ?? 'collection';
    }

    public function responses()
    {
        return $this->hasMany(\App\Models\CollectionResponse::class, 'collection_id', 'id');
    }

    public function fields()
    {
        return $this->hasMany(\App\Models\CollectionField::class, 'collection_id', 'id')
            ->select(['id', 'collection_id', 'sub_section', 'reference', 'value']);
    }

    public function collections()
    {
        $hasMany = $this->hasMany(\App\Models\Collection::class, 'parent_id', 'id');
        if (isset(request()->with)) {
            $hasMany->whereIn('slug', explode(',', request()->with));
        }

        $hasMany->select([
            'id',
            'form_id',
            'parent_id',
            'specific_id',
            'slug',
            'created_at',
            'created_by',
            'updated_at',
            'updated_by',
        ]);
        return $hasMany;
        // return $hasMany->limit(60);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)->firstOrFail();
    }
}
