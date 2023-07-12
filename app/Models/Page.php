<?php

namespace App\Models;

use App\Models\PageComponent;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory, Uuids;

    protected $table = 'page';

    protected $fillable = [
        'id',
        'parent_id',
        'name',
        'icon',
        'path',
        'show',
        'module',
        'permission',
        'category',
        'order',
    ];

    protected static function boot()
    {
        parent::boot();

        if (request()->isMethod('post')) {
            self::addCreatingUuid();
            return;
        }

        if (empty(request()->user()->id)) {
            // static::addGlobalScope('public', function (Builder $builder) {
            //     $builder->where('public', 1);
            // });
            // return;
        }

        // if (request()->user()->ims_account == 1) {
        //     return;
        // }

        static::addGlobalScope('permissions', function (Builder $builder) {
            if (empty(request()->user()->role_id)) {
                return;
            }
            $builder->where(function ($where) {
                $where->whereNull('permission')
                    ->orWhereRaw(
                        "0 != (
                            SELECT `id`
                            FROM `role`
                            WHERE `id` = '" . request()->user()->role_id . "'
                                AND !isNull(JSON_SEARCH(`role`.`permissions`, 'one', `permission`))
                        )"
                    );
            });
        });

        static::deleting(function ($page) {
            $page->components()->delete();
        });
    }

    public function components()
    {
        return $this->hasMany(PageComponent::class)->orderBy('order');
    }
}
