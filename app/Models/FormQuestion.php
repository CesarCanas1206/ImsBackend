<?php

namespace App\Models;

use App\Models\Form;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormQuestion extends Model
{
    use HasFactory, Uuids;

    protected $table = 'form_question';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'text',
        'form_id',
        'parent_id',
        'response',
        'component',
        'reference',
        'question_order',
        'props',
        'form_props',
        'show_form',
        'show_report',
        'enabled',
    ];

    protected $casts = [
        'props' => 'array',
        'form_props' => 'array',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
