<?php

namespace App\Http\Controllers;

use App\Models\CollectionField;
use App\Models\CollectionHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class APIController extends Controller
{
    protected $tmpLoaded = [];

    protected function successResponse($data, $code = 200)
    {
        return Response()->json($data, $code);
    }

    protected function errorResponse($message, $code = 409)
    {
        return Response()->json(['error' => $message, 'code' => $code], $code);
    }

    public function hasCache($key = null)
    {
        return false;
        return cache($key ?? $this->cacheUrl());
    }

    public function showAll(Collection $collection, $code = 200)
    {
        $collection = $this->filterData($collection);
        $collection = $this->sortData($collection);
        $collection = $this->adjustData($collection);

        $collection = $this->cacheResponse($collection->values());
        return $this->successResponse(['data' => $collection], $code);
    }

    public function showOne(Model $model, $code = 200)
    {
        return $this->successResponse(['data' => $model], $code);
    }

    public function clearCache()
    {
        Cache::flush();
    }

    public function filterData(Collection $collection)
    {
        foreach (request()->query() as $query => $value) {
            if (in_array($query, ['sort_by', 'sort', 'fields', 'include', 'skip', 'with', 'limit', 'join', 'except', 'withParent', 'withAuthor', 'withHirer', 'joinAssetForm', 'hirerAssets']) ||
                strstr($query, 'with') || strstr($query, 'sort') || strstr($query, 'only')) {
                continue;
            }
            $firstCharacter = substr($value, 0, 1);
            if ($value === 'me') {
                $value = request()->user()->id;
            }
            $attribute = $query;

            if ($value === 'empty') {
                $collection = $collection->filter(function ($item) use ($attribute) {
                    return empty($item->{$attribute}) || $item->{$attribute} === 'No';
                });

                continue;
            }
            if ($value === '!empty') {
                $collection = $collection->filter(function ($item) use ($attribute) {
                    return isset($item->{$attribute}) && !empty($item->{$attribute}) && $item->{$attribute} !== 'No';
                });
                continue;
            }

            if ($firstCharacter === '%') {
                $collection = $collection->filter(function ($item) use ($value, $attribute) {
                    return false !== stristr($item->{$attribute}, str_replace('%', '', $value));
                });
                continue;
            }

            if ($firstCharacter === '>') {
                $collection = $collection->where($attribute, '>=', str_replace('>', '', $value));
                continue;
            }

            if ($firstCharacter === '<') {
                $collection = $collection->where($attribute, '<=', str_replace('<', '', $value));
                continue;
            }

            if ($firstCharacter === '!') {
                $collection = $collection->where($attribute, '!=', str_replace('!', '', $value));
                continue;
            }

            if ($firstCharacter === '[') {
                $value = explode(',', str_replace(['!', '[', ']'], '', $value));
                $collection = $collection->filter(function ($item) use ($value, $attribute) {
                    if (!isset($item->$attribute)) {
                        return true;
                    }
                    return $this->checkIfFound($value, $item->$attribute);
                });
                continue;
            }

            if (substr($value, 0, 2) === '![') {
                $value = explode(',', str_replace(['!', '[', ']'], '', $value));
                $collection = $collection->filter(function ($item) use ($value, $attribute) {
                    return !$this->checkIfFound($value, $item->$attribute);
                });
                continue;
            }

            $collection = $collection->where($attribute, $value);
        }
        return $collection;
    }

    public function adjustData(Collection $collection)
    {
        $queries = request()->query();
        if (!isset($queries['fields']) && !isset($queries['skip']) && !isset($queries['limit'])) {
            return $collection;
        }

        if (isset($queries['fields'])) {
            $fields = explode(',', $queries['fields']) ?? ['id'];
            return $collection->map(function ($collect) use ($fields) {
                return $collect->only($fields);
            });
        }

        if (isset($queries['skip'])) {
            $skip = explode(',', $queries['skip']) ?? ['id'];
            return $collection->map(function ($collect) use ($skip) {
                return $collect->except($skip);
            });
        }

        if (isset($queries['limit'])) {
            return $collection->slice(0, $queries['limit']);
        }
    }

    public function valueAsArrayOrString($value)
    {
        return substr($value, 0, 2) == '["' ? json_decode($value, true) : explode(',', $value);
    }

    public function checkIfFound($value, $check)
    {
        $check = $this->valueAsArrayOrString($check);
        if (!is_array($check)) {
            return in_array($check, $value);
        }
        $found = false;
        if (is_array($value)) {
            foreach ($value as $v) {
                if (in_array($v, $check)) {
                    $found = true;
                }
            }
        }

        return $found;
    }

    public function sortData(Collection $collection)
    {
        if (request()->has('sort_by')) {
            $attribute = request()->sort_by;
            $collection = $collection->sortBy->{$attribute};
        } elseif (request()->has('sort')) {
            $attribute = request()->sort;
            $collection = $collection->sortBy->{$attribute};
        }
        return $collection;
    }

    public function paginate(Collection $collection)
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
    }

    public function cacheUrl()
    {
        $url = request()->url();
        $queryParams = request()->query();
        foreach ($queryParams as $key => $value) {
            if ($value === 'me') {
                $queryParams[$key] = request()->user()->id;
            }
        }
        ksort($queryParams);
        $queryString = http_build_query($queryParams);
        $siteName = request()->header('x-site-name') ?? 'bookings';
        $fullUrl = "${url}${siteName}?${queryString}";
        return $fullUrl;
    }

    public function cacheResponse($data)
    {
        return $data; //turn off using the casheURL()

        $fullUrl = $this->cacheUrl();
        // $site_name = request()->header('x-site-name') ?? 'bookings';
        // Cache::tags([$site_name])->flush();
        // return Cache::tags([$site_name])->put($fullUrl, $data, 5 * 60);

        return Cache::remember($fullUrl, 30 * 60, function () use ($data) {
            return $data;
        });
    }

    public function loadValue($key, $row)
    {
        if (strstr($key, '.')) {
            $next = explode('.', $key);
            $first = array_shift($next);
            return $this->loadValue(implode('.', $next), $row->{$first});
        }
        return $row->{$key} ?? '';
    }

    public function mapFieldsToValues($collection, $withJoin = true)
    {
        if (request()->has('with')) {
            $with = explode(',', request()->with);
            foreach ($with as $w) {
                if (isset($collection->{$w})) {
                    $collection->{$w}->map([$this, 'mapFieldsToValues']);
                }
            }
        }

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

            if ($key === 'user_id' && $withJoin) {
                $prefix = str_replace('_id', '', $key);
                if (!isset($this->tmpLoaded[$field->value])) {
                    $this->tmpLoaded[$field->value] = User::where('id', $value)
                        ->get(['name', 'first_name', 'last_name', 'email'])
                        ->map([$this, 'mapFieldsToValues'])
                        ->first();
                }
                $joined = $this->tmpLoaded[$field->value];
                $collection->{$prefix} = $joined;
            }

            $collection->{$key} = $value;

            if (strstr($key, 'ai::') || $key == 'application_id') {
                $collection->{str_replace('ai::', '', $key)} = $value;
            }
        });

        unset($collection->fields);
        return $collection;
    }

    /**
     * Set up the collection reference pointing to the right table
     */
    public function setupCollectionRef($reference = null)
    {
        $collection = \DB::table('schema')->where('slug', $reference)->pluck('set')->first();

        $_SERVER['x-collection-ref'] = $collection ?? 'collection';
    }

    public function addResponses(Request $request, $id, $class = null)
    {
        if ($class) {
            $responses = $request->except((new $class)->getFillable());
        } else {
            $responses = $request->all();
        }

        $skipResponses = [
            'id',
            'collection_id',
            'reference',
            'value',
            'created_at',
            'updated_at',
            'questions',
            'form_id',
        ];

        $getOriginals = CollectionField::where('collection_id', $id)
            ->whereIn('reference', array_keys($responses))
            ->get(['reference', 'value']);

        $originals = [];
        foreach ($getOriginals as $original) {
            $originals[$original->reference] = $original->value;
        }

        foreach ($responses as $ref => $value) {
            if (in_array($ref, $skipResponses)) {
                continue;
            }
            $values = [
                'collection_id' => $id,
                'reference' => $ref,
                'value' => $value,
            ];
            $check = ['collection_id' => $id, 'reference' => $ref];

            $original = $originals[$ref] ?? null;

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

            /** Record history for this change */
            $values['value'] = ['from' => $original ?? '', 'to' => $values['value']];
            if (empty($original)) {
                unset($values['value']['from']);
            }
            $values['created_by'] = Request()->user()->id ?? '';
            CollectionHistory::create($values);
        }
    }
}
