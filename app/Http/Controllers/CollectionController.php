<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\CollectionHistory;
use App\Models\Form;
use App\Models\User;
use DB;
use Illuminate\Http\Request;

class CollectionController extends APIController
{
    protected $depth = 0;
    protected $tmpLoaded = [];
    protected $tmpCollections = [];
    protected $defaultSelect = [
        'collection.id',
        'collection.form_id',
        'collection.parent_id',
        'collection.specific_id',
        'collection.slug',
        'collection.created_at',
        'collection.created_by',
        'collection.updated_at',
        'collection.updated_by',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Collection $collection)
    {
        return $this->showAll(Collection::form('closure')->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $reference = null, $noShow = false)
    {
        if (!is_numeric($request->form_id)) {
            $request->merge([
                'form_id' => Form::where('reference', $request->form_id)
                    ->orWhere('id', $request->form_id)
                    ->pluck('reference')
                    ->first() ?? $request->form_id,
            ]);
        }
        $reference = str_replace(
            'd/',
            '',
            $reference ??
            $request->slug ??
            Form::where('id', $request->form_id)->orWhere('reference', $request->form_id)->pluck('endpoint')->first()
        );

        $new = Collection::create(
            array_merge(
                $request->all(),
                [
                    'slug' => $reference,
                ]
            )
        );

        $this->addResponses($request, $new->id);

        if (!empty($request->sub)) {
            $subList = $request->sub;
            foreach ($subList as $sub) {
                $sub['parent_id'] = $new->id;
                $subRequest = new Request($sub);
                $this->store($subRequest, $sub['slug'] ?? null, true);
            }
        }

        if ($noShow) {
            return $new;
        }

        $this->clearCache();

        return $this->collections($reference, $new->id);
    }

    /**
     * Set up the collection reference pointing to the right table
     */
    public function setupCollectionRef($reference = null)
    {
        $collection = \DB::table('schema')->where('slug', $reference)->pluck('set')->first();

        $_SERVER['x-collection-ref'] = $collection ?? 'collection';
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Collection  $collection
     * @return \Illuminate\Http\Response
     */
    public function show($reference)
    {
        return $this->collections($reference);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Collection  $collection
     * @return \Illuminate\Http\Response
     */
    public function single($reference, $id)
    {
        return $this->collections($reference, $id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Collection  $collection
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Collection $collection)
    {
        $collection->fill($request->all())->save();

        $this->addResponses($request, $collection->id);

        $this->clearCache();

        return $this->collections($collection->slug, $collection->id);
    }

    public function updatePath(Request $request, $id, Collection $collection)
    {
        return $this->update($request, $collection);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Collection  $collection
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Collection $collection)
    {
        $this->setupCollectionRef($collection->slug);
        $collection->delete();

        $collectionFields = CollectionField::where('collection_id', $collection->id)->get(['id']);
        CollectionField::destroy($collectionFields->toArray());

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $collection->id]);
    }

    public function addResponses(Request $request, $id, $class = null)
    {
        $responses = !empty($request->responses)
        ? $request->responses
        : $request->except(['id', 'parent_id', 'specific_id', 'form_id', 'slug', 'updated_at', 'created_at', 'sub']);

        if (!empty($responses)) {
            foreach ($responses as $ref => $value) {
                $values = [
                    'collection_id' => $id,
                    'reference' => $ref,
                    'value' => $value,
                ];
                $check = ['collection_id' => $id, 'reference' => $ref];
                $original = CollectionField::where('collection_id', $id)->where('reference', $ref)->select('value')->first();
                if (strstr($ref, 'ai::')) {
                    $max = CollectionField::where(function ($query) use ($ref) {
                        $query->where('reference', $ref)
                            ->orWhere('reference', str_replace('ai::', '', $ref));
                    })
                        ->where('value', 'not like', "%-%")
                        ->selectRaw('max(cast(value as Unsigned)) as value')
                        ->pluck('value')
                        ->first();

                    $values['value'] = (int) ($max ?? 0) + 1;
                } elseif (strstr($ref, 'id') && $value == 'me') {
                    $values['value'] = request()->user()->id;
                } elseif (!is_array($value) && substr($value, 0, 5) === 'data:') {
                    $values['value'] = FileController::store($value);
                } elseif (strstr($ref, ':')) {
                    list($ref, $sub_section) = explode(':', $ref);
                    $check['reference'] = $ref;
                    $check['sub_section'] = $sub_section;
                    $values['reference'] = $ref;
                    $values['sub_section'] = $sub_section;
                } elseif (is_array($values['value'])) {
                    $values['value'] = json_encode($values['value']);
                }

                CollectionField::updateOrCreate($check, $values);
                $values['value'] = ['from' => $original->value ?? '', 'to' => $values['value']];
                if (empty($original->value)) {
                    unset($values['value']['from']);
                }
                $values['created_by'] = Request()->user()->id ?? '';
                CollectionHistory::create($values);
            }
        }
    }

    // public function filterCollection(Collection $collection)
    // {
    //     foreach (request()->query() as $query => $value) {
    //         if (in_array($query, ['sort_by', 'fields', 'include', 'skip', 'with'])) {
    //             continue;
    //         }
    //         $firstCharacter = substr($value, 0, 1);
    //         if ($value === 'me') {
    //             $value = request()->user()->id;
    //         }
    //         $attribute = $query;

    //         if ($value === 'empty') {
    //             $collection = $collection->filter(function ($item) use ($attribute) {
    //                 return empty($item->{$attribute});
    //             });

    //             continue;
    //         }
    //         if ($value === '!empty') {
    //             $collection = $collection->filter(function ($item) use ($attribute) {
    //                 return isset($item->{$attribute}) && !empty($item->{$attribute});
    //             });
    //             continue;
    //         }

    //         if ($firstCharacter === '%') {
    //             $collection = $collection->filter(function ($item) use ($value, $attribute) {
    //                 return false !== stristr($item->{$attribute}, str_replace('%', '', $value));
    //             });
    //             continue;
    //         }

    //         if ($firstCharacter === '>') {
    //             $collection = $collection->where($attribute, '>=', str_replace('>', '', $value));
    //             continue;
    //         }

    //         if ($firstCharacter === '<') {
    //             $collection = $collection->where($attribute, '<=', str_replace('<', '', $value));
    //             continue;
    //         }

    //         if ($firstCharacter === '!') {
    //             $collection = $collection->where($attribute, '!=', str_replace('!', '', $value));
    //             continue;
    //         }

    //         if ($firstCharacter === '[') {
    //             $value = explode(',', str_replace(['!', '[', ']'], '', $value));
    //             $collection = $collection->filter(function ($item) use ($value, $attribute) {
    //                 return $this->checkIfFound($value, $item->$attribute);
    //             });
    //             continue;
    //         }

    //         if (substr($value, 0, 2) === '![') {
    //             $value = explode(',', str_replace(['!', '[', ']'], '', $value));
    //             $collection = $collection->filter(function ($item) use ($value, $attribute) {
    //                 return !$this->checkIfFound($value, $item->$attribute);
    //             });
    //             continue;
    //         }

    //         $collection = $collection->where($attribute, $value);
    //     }
    //     return $collection;
    // }

    public function mapFieldsToValues($collection, $withJoin = false)
    {
        if (!isset($collection->fields)) {
            return $collection;
        }

        $collection->fields->map(function ($field) use (&$collection, $withJoin) {
            $key = !empty($field->sub_section)
            ? $field->reference . ':' . $field->sub_section
            : $field->reference;

            $value = substr($field->value, 0, 2) === '{"' ||
            substr($field->value, 0, 3) === '{\"' ||
            substr($field->value, 0, 3) === '[\"' ||
            substr($field->value, 0, 2) === '[{'
            ? json_decode(stripslashes($field->value), true)
            : str_replace('\\n', '\n', $field->value);

            $collection->{$key} = $value;

            if (strstr($key, 'ai::') || $key == 'application_id') {
                $collection->{str_replace('ai::', '', $key)} = $value;
                return;
            }

            if ($withJoin) {
                $this->joinCollection($collection, $field, $key, $value);
            }
        });

        if ($withJoin) {
            $this->joinWith($collection);
        }
        unset($collection->fields);
        return $collection;
    }

    public function joinWith(&$collection)
    {
        if (request()->has('withParent') && !empty($collection->parent_id)) {
            if (!isset($this->tmpLoaded[$collection->parent_id])) {
                $parent = CollectionField::where('collection_id', $collection->parent_id)
                    ->where('reference', 'name')
                    ->pluck('value')
                    ->first();
                if ($parent) {
                    $collection->parent = $parent;
                }
                $this->tmpLoaded[$collection->parent_id] = $parent;
            } else {
                $collection->parent = $this->tmpLoaded[$collection->parent_id];
            }
        }

        if (request()->has('withAuthor')) {
            $check = ['created', 'updated', 'completed'];
            foreach ($check as $key) {
                if (!empty($collection->{$key . '_by'})) {
                    if (!isset($this->tmpLoaded[$collection->{$key . '_by'}])) {
                        $author = User::where('id', $collection->{$key . '_by'})
                            ->get(['name'])
                            ->pluck('name')
                            ->first();
                        if ($author) {
                            $collection->{$key . '_name'} = $author;
                        }
                        $this->tmpLoaded[$collection->{$key . '_by'}] = $author;
                    } else {
                        $collection->{$key . '_name'} = $this->tmpLoaded[$collection->{$key . '_by'}];
                    }
                }
            }
        }

        if (request()->has('withHirer')) {
            $check = ['responsibility'];
            foreach ($check as $key) {
                if (!empty($collection->{$key . '_by'}) && $collection->{$key . '_by'} !== 'Council') {
                    if (!isset($this->tmpLoaded[$collection->{$key . '_by'}])) {
                        $hirer = CollectionField::where('collection_id', $collection->{$key . '_by'})
                            ->where('reference', 'name')
                            ->pluck('value')
                            ->first();
                        if ($hirer) {
                            $collection->{$key . '_name'} = $hirer;
                        }
                        $this->tmpLoaded[$collection->{$key . '_by'}] = $hirer;
                    } else {
                        $collection->{$key . '_name'} = $this->tmpLoaded[$collection->{$key . '_by'}];
                    }
                }
            }
        }
    }

    public function joinCollection(&$collection, $field, $key, $value)
    {
        if ($key == 'specific_id') {
            return;
        }

        if (request()->has('fields')) {
            $fields = explode(',', request()->fields);
            if (!in_array($field->reference, $fields)) {
                return;
            }
        }

        if (in_array(substr($field->reference, -3), ['-id', '_id']) && !empty($field->value)) {
            $prefix = str_replace('_id', '', $field->reference);
            if ($prefix == 'user') {
                if (!isset($this->tmpLoaded[$field->value])) {
                    $this->tmpLoaded[$field->value] = User::where('id', $field->value)
                        ->get(['name', 'first_name', 'last_name', 'email'])
                        ->first();
                }
                $joined = $this->tmpLoaded[$field->value];
                $collection->{$prefix} = $joined;
            } elseif ($prefix == 'form') {
                $joined = Form::where('id', $field->value)->get(['name'])->first();
                $collection->{$prefix} = $joined;
            } elseif ($prefix == 'asset') {
                $joined = \App\Models\Asset::where('id', $field->value)->without(['parent', 'fields'])->get(['name']);
                $collection->{$prefix} = $joined;
            } else {
                $values = substr($field->value, 0, 2) === '["'
                ? json_decode($field->value, true)
                : explode(',', $field->value);
                if (!isset($this->tmpLoaded[$field->value])) {
                    $items = [];

                    Collection::whereIn('id', array_filter($values))
                        ->get()
                        ->map(function ($joined) use (&$items) {
                            return $this->mapFieldsToValues($joined, true);
                        })
                        ->each(function ($joined) use (&$items) {
                            $items[] = $joined;
                        });

                    $this->tmpLoaded[$field->value] = array_filter($items);
                } else {
                    $items = $this->tmpLoaded[$field->value];
                }
                if (!empty($items)) {
                    if (!isset($collection->{$prefix})) {
                        $collection->{$prefix} = [];
                    }
                    if (is_array($collection->{$prefix})) {
                        if (is_array($values)) {
                            $collection->{$prefix} = array_merge($collection->{$prefix}, $items);
                        } else {
                            $collection->{$prefix} = array_merge(
                                $collection->{$prefix},
                                ...$items
                            );
                        }
                    }
                }
            }
        }
    }

    public function formatFields($collection)
    {
        $this->depth += 1;
        $collection = $this->mapFieldsToValues($collection, true);

        $hasJoin = request()->has('join');
        if ($hasJoin
        ) {
            $join = request()->join;
            $join = explode(',', $join);
            foreach ($join as $j) {
                list($table, $field, $check) = explode(':', $j);
                $this->setupCollectionRef($table);
                $collections = Collection::where('slug', $table);
                $collections = $collections->join(
                    CollectionField::getTable(),
                    CollectionField::getTable() . '.collection_id',
                    'collection.id'
                )
                    ->where(CollectionField::getTable() . '.reference', $field)
                    ->where(
                        CollectionField::getTable() . '.value',
                        'like',
                        '%' . (isset($collection->{$check}) ? $collection->{$check} : $check) . '%'
                    )
                    ->select($this->defaultSelect)
                    ->get();

                if (!empty($collections)) {
                    if (!isset($collection->{$table})) {
                        $collection->{$table} = [];
                    }

                    $collection->{$table} = array_merge(
                        $collection->{$table},
                        $collections->map([$this, 'formatFields'])->toArray()
                    );
                }
            }
        }

        $hasWith = request()->has('with');
        if ($hasWith) {
            $collection = $this->addWithToCollection($collection);
        }

        unset($collection->collections);
        return $collection;
    }

    public function addWithToCollection($collection)
    {
        $collection->collections
            ->each(function ($sub) use (&$collection) {
                $slug = $sub->slug ?? '';
                if (empty($slug)) {
                    return $sub;
                }
                $sub = $this->mapFieldsToValues($sub);
                $sub = $this->addWithToCollection($sub);
                unset($sub->collections);
                $collection->{$slug} = array_merge($collection->{$slug} ?? [], [$sub]);
            });
        return $collection;
    }

    public function joinAssetForm($collection)
    {
        $key = 'asset-form';
        $mapped = [];

        $join = Collection::select(['id', 'form_id'])->with('fields');
        $join->where('slug', $key);

        $join = $join->get();
        $join = $join->map(function (&$item) {
            $item->fields->map(function ($field) use (&$item) {
                $item->{$field->reference} = $field->value;
            });
            unset($item->fields);
            return $item;
        });

        $join->each(function ($item) use (&$mapped) {
            if (!isset($mapped[$item->asset_id])) {
                $mapped[$item->asset_id] = [];
            }
            $mapped[$item->asset_id][] = $item;
        });

        $collection->map(function (&$item) use ($mapped, $key) {
            if (isset($mapped[$item->id])) {
                $item->{$key} = $mapped[$item->id];
            }
        });
    }

    public function filterCollection(&$collection)
    {
        $checks = ['hirer_id', 'user_id', 'inspectable'];
        foreach ($checks as $check) {
            if (request()->has($check)) {
                $checkValue = request()->{$check};
                if ($checkValue === 'empty' || $checkValue === '!empty') {
                    continue;
                } elseif ($checkValue === 'me') {
                    $checkValue = [request()->user()->id];
                } else {
                    $checkValue = explode(',', str_replace(['!', '[', ']'], '', $checkValue));
                }
                $collection->join('collection_field', function ($join) use ($check, $checkValue) {
                    $join->on('collection.id', '=', 'collection_field.collection_id')
                        ->where('collection_field.reference', $check)
                        ->where('collection_field.value', $checkValue);
                });
            }
        }
    }

    public function collections($reference, $id = null)
    {
        $cached = $this->hasCache();
        if ($cached) {
            return $this->successResponse(['data' => $cached]);
        }
        $this->setupCollectionRef($reference);

        $collection = Collection::select($this->defaultSelect);

        $this->filterCollection($collection);

        if (request()->has('with')) {
            $collection = $collection->with('collections');
        }

        if (request()->has('limit')) {
            $collection = $collection->limit(request()->limit);
        }

        if (request()->has('hirerAssets')) {
            $check = request()->hirerAssets;
            $checkIds = Collection::where('slug', 'asset-hirer')->with([])
                ->select(['asset.value'])
                ->join('collection_field', function ($join) use ($check) {
                    $join->on('collection.id', '=', 'collection_field.collection_id')
                        ->where('collection_field.reference', 'hirer_id')
                        ->where('collection_field.value', $check);
                })
                ->join('collection_field as asset', function ($join) {
                    $join->on('collection.id', '=', 'asset.collection_id')
                        ->where('asset.reference', 'asset_id');
                })
                ->get()
                ->pluck('value');

            if (count($checkIds) > 0) {
                $getIds = $checkIds;
            }
        }

        if (!empty($getIds)) {
            $collection = $collection->whereIn('collection.id', $getIds);
        } elseif (!empty($id)) {
            $collection = $collection->where('collection.id', $id);
        } else {
            $collection = $collection->where('slug', $reference);

            $checks = ['parent_id', 'specific_id', 'id'];
            foreach ($checks as $check) {
                if (request()->has($check) && !in_array(request()->{$check}, ['empty', '!empty'])) {
                    $value = explode(',', str_replace(['!', '[', ']'], '', request()->{$check}));
                    $collection = $collection->whereIn($check, $value);
                }
            }
        }
        $result = $collection->get();
        $result = $result->map([$this, 'formatFields']);
        if (request()->has('withAssetForm')) {
            $this->joinAssetForm($result);
        }

        if (!empty($id)) {
            return $this->showOne($result->first());
        }
        return $this->showAll($result);
    }

    /**
     * Returns the collections for calendar items
     * Accepts a range parameter in the format of start,end
     * @return \Illuminate\Http\JsonResponse
     */
    public function calendar()
    {
        $cached = $this->hasCache();
        if ($cached) {
            return $this->successResponse(['data' => $cached]);
        }
        $this->setupCollectionRef('calendar');

        if (request()->has('range')) {
            [$start, $end] = explode(',', request()->range);
            $collectionsStart = DB::select(
                "SELECT collection_id FROM " . CollectionField::getTable() . " start
                WHERE start.reference = 'start' AND start.value >= ?",
                [$start]
            );
            $collectionStartIds = array_column($collectionsStart, 'collection_id');

            $collectionsEnd = DB::select(
                "SELECT collection_id FROM " . CollectionField::getTable() . " end
                WHERE end.reference = 'end' AND end.value <= ?",
                [$end]
            );
            $collectionEndIds = array_column($collectionsEnd, 'collection_id');

            $collectionIds = array_intersect($collectionStartIds, $collectionEndIds);

            $collection = Collection::select([
                'collection.id',
                'collection.form_id',
                'collection.parent_id',
                'collection.specific_id',
                'collection.slug',
            ])
                ->where('slug', 'calendar')
                ->whereIn('id', $collectionIds);

            $result = $collection->get();

            $result->map(function ($collection) {
                $collection->fields->map(function ($field) use (&$collection) {
                    $key = $field->reference;
                    $value = $field->value;
                    $collection->{$key} = $value;
                });
                unset($collection->fields);
            });

            $result = $this->cacheResponse($result->toArray());
            return $this->successResponse(['data' => $result]);
        }

        $collection = Collection::select([
            'collection.id',
            'collection.form_id',
            'collection.parent_id',
            'collection.specific_id',
            'collection.slug',
            'collection.created_at',
            'collection.created_by',
            'collection.updated_at',
            'collection.updated_by',
        ]);

        $collection = $collection->where('slug', 'calendar');

        if (request()->has('range')) {
            [$start, $end] = explode(',', request()->range);
            $collection->join(CollectionField::getTable() . ' as start', function ($join) use ($start) {
                $join->on('start.collection_id', '=', 'collection.id')
                    ->where('start.reference', 'start')
                    ->where('start.value', '>=', $start);
            });

            $collection->join(CollectionField::getTable() . ' as end', function ($join) use ($end) {
                $join->on('end.collection_id', '=', 'collection.id')
                    ->where('end.reference', 'end')
                    ->where('end.value', '<=', $end);
            });
            request()->request->remove('range');
        }

        $result = $collection->get();

        $result->map(function ($collection) {
            $collection->fields->map(function ($field) use (&$collection) {
                $key = $field->reference;
                $value = $field->value;
                $collection->{$key} = $value;
            });
            unset($collection->fields);
        });

        return $this->showAll($result);
    }

    public function deleteMultiple(Request $request, $reference, $id)
    {
        Collection::where('slug', $reference)
            ->where('specific_id', $id)
            ->get()
            ->each(function ($collection) {
                $collection->fields()->delete();
                $collection->delete();
            });

        $this->clearCache();

        return response()->json(['data' => 'Deleted multiple']);
    }

    public function loadReference($reference, $id = null)
    {
        $collectionModel = new Collection;
        $collection = $collectionModel->select([
            $collectionModel->getTable() . '.id',
            'form_id',
            'specific_id',
            $collectionModel->getTable() . '.created_at',
            $collectionModel->getTable() . '.updated_at',
        ]);

        if (!empty($id)) {
            $collection = $collection->where('id', $id);
        }

        return $collection
            ->get()
            ->map([$this, 'formatFields']);
    }
}
