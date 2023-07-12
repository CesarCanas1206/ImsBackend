<?php

namespace App\Models;

use App\Models\FormQuestion;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory, Uuids;

    protected $table = 'form';
    protected $with = ['fields'];

    protected $fillable = [
        'id',
        'parent_id',
        'name',
        'reference',
        'category',
        'description',
        'props',
        'endpoint',
        'custom',
        'allow_multiple',
        'reminders',
        'enabled',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function questions()
    {
        return $this->hasMany(FormQuestion::class)
            ->select(['id', 'form_id', 'parent_id', 'reference', 'text', 'props', 'form_props', 'component', 'repeat', 'show_form', 'show_report', 'question_order'])
            ->where('enabled', 1)
            ->orderBy('question_order');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)->orWhere('reference', $value)->firstOrFail();
    }

    public function assetForms()
    {
        return $this->hasMany(\App\Models\AssetForm::class, 'form_id', 'id');
    }

    public function fields()
    {
        return $this->hasMany(\App\Models\CollectionField::class, 'collection_id', 'id')
            ->select(['id', 'collection_id', 'reference', 'value']);
    }

}
