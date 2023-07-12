<?php
namespace App\Traits;

use Illuminate\Support\Str;
trait Uuids
{
    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
            if (!empty(request()->user()->id)) {
                $model->created_by = request()->user()->id;
            }
        });
        static::updating(function ($model) {
            if (!empty(request()->user()->id)) {
                $model->updated_by = request()->user()->id;
            }
        });
        static::deleting(function ($model) {
            if (!empty(request()->user()->id)) {
                $model->deleted_by = request()->user()->id;
            }
        });
    }
    /**
     * Setup the static::creating
     * Used if a boot method is already set up in a model
     */
    public static function addCreatingUuid()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
            if (!empty(request()->user()->id)) {
                $model->created_by = request()->user()->id;
            }
        });
        static::updating(function ($model) {
            if (!empty(request()->user()->id)) {
                $model->updated_by = request()->user()->id;
            }
        });
        static::deleting(function ($model) {
            if (!empty(request()->user()->id)) {
                $model->deleted_by = request()->user()->id;
            }
        });
    }
    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }
    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }
    /**
     * Get the name of the key.
     *
     * @return string
     */
    public function getKeyName()
    {
        return 'id';
    }
}
