<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    use HasFactory, Uuids;

    protected $table = 'dataset';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('slug', $value)->orWhere('id', $value)->firstOrFail();
    }
}
