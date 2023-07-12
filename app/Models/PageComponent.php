<?php

namespace App\Models;

use App\Models\Page;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageComponent extends Model
{
    use HasFactory, Uuids;

    protected $table = 'page_component';

    protected $fillable = [
        'page_id',
        'parent_id',
        'component',
        'props',
        'permission',
        'order',
    ];

    protected $casts = [
        'props' => 'object',
    ];

    protected static function boot()
    {
        parent::boot();

        if (request()->isMethod('post')) {
            self::addCreatingUuid();
            return;
        }

        if (empty(Request()->user())) {
            // static::addGlobalScope('public', function (Builder $builder) {
            //     $builder->where('public', 1);
            // });
            return;
        }

        if (Request()->user()->ims_account == 1) {
            return;
        }

        static::addGlobalScope('permissions', function (Builder $builder) {
            $builder->where(function ($where) {
                $where->whereNull('permission')
                    ->orWhere('permission', '')
                    ->orWhereRaw(
                        "0 != (
                            SELECT `id`
                            FROM `role`
                            WHERE `id` = '" . Request()->user()->role_id . "'
                                AND !isNull(JSON_SEARCH(`role`.`permissions`, 'one', `permission`))
                        )"
                    );
            });
        });
    }

    public function page()
    {
        return $this->hasOne(Page::class);
    }
}
