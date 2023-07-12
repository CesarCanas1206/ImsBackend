<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fee extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    protected $table = 'fees';

    protected $fillable = [
        'id',
        'hirer_id',
        'booking_id',
        'asset_id',
        'usage_id',
        'name',
        'start',
        'end',
        'rate',
        'unit',
        'total',
    ];

}
