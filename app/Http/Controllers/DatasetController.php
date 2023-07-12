<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use \App\Http\Controllers\CollectionController;

class DatasetController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->showAll(Dataset::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
        ];

        $this->validate($request, $rules);

        $new = Dataset::create($request->all());

        $this->clearCache();

        return $this->showOne($new, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Dataset  $dataset
     * @return \Illuminate\Http\Response
     */
    public function show(Dataset $dataset)
    {
        return $this->showOne($dataset);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Dataset  $dataset
     * @return \Illuminate\Http\Response
     */
    public function data(Dataset $dataset)
    {
        $cached = $this->hasCache();
        if ($cached) {
            return $this->successResponse($cached);
        }

        $data = !is_array($dataset->data) ? json_decode($dataset->data, true) : $dataset->data;

        $resultData = [];
        $results = [];
        $hasData = false;
        foreach ($data as $params) {
            if (isset($params['endpoint'])) {
                $queryParams = parse_url($params['endpoint']);
                if (isset($queryParams['query'])) {
                    parse_str($queryParams['query'], $query);
                    request()->query->add($query);
                }

                $reference = trim(str_replace('d/', '', $queryParams['path']), '/');

                $collection = new CollectionController();
                return $collection->collections($reference);
            }
            if (isset($params['collection'])) {
                $model = new CollectionController();
                $model = $model->loadReference($params['collection']);

            } elseif (isset($params['className']) && $params['className'] == 'Collection') {
                $model = new CollectionController();
                // $model = $collection->loadReference($params['form']);
                if (isset($params['scope'])) {
                    foreach ($params['scope'] as $scope => $value) {
                        $model = $model->loadReference($value);
                    }
                }

                if (!empty($params['count'])) {
                    foreach ($params['count'] as $countData) {
                        $table = $countData['table'] ?? 'collection_field';
                        $key = $countData['key'] ?? 'jobs';
                        if ($table != 'collection_field') {
                            $model = $model->map(function ($t) use ($countData, $table, $key) {
                                $t->{$key} = \DB::table($table)
                                    ->where($table . '.' . $countData['on'], $t->{$countData['value']})
                                // ->where('form_response.value', )
                                    ->count();
                                return $t;
                            });
                        } else {
                            $model = $model->map(function ($t) use ($countData, $table, $key) {
                                $t->{$key} = \DB::table($table)
                                    ->join('collection', 'collection_field.collection_id', 'collection.id')
                                    ->join('form', 'form.id', 'collection.form_id')
                                    ->where('collection_field.reference', $countData['on'])
                                    ->where('collection_field.value', $t->{$countData['value']})
                                    ->count();
                                return $t;
                            });
                        }
                    }
                }
            } else {
                $modelClass = '\App\Models\\' . ($params['className'] ?? 'Application');
                $model = new $modelClass;
                if (isset($params['with'])) {
                    $model = $model->with($params['with']);
                }
                if (isset($params['filter'])) {
                    foreach ($params['filter'] as $key => $value) {
                        $model = $model->where($key, $value);
                    }
                }
                if (isset($params['scope'])) {
                    foreach ($params['scope'] as $scope => $value) {
                        $model = $model->{$scope}($value ?? '');
                    }
                }
                $model = $model->get();
            }

            if (!empty($params['join'])) {
                foreach ($params['join'] as $joinData) {
                    $table = $joinData['table'] ?? 'collection_field';
                    $key = $joinData['key'] ?? 'jobs';
                    $model->map(function ($t) use ($joinData, $table, $key) {
                        $join = \DB::table($table)
                            ->select('collection.*')
                            ->join('collection', 'collection_field.collection_id', 'collection.id')
                            ->join('form', 'form.id', 'collection.form_id')
                            ->where('collection_field.reference', $joinData['on'])
                            ->where('collection_field.value', $t->{$joinData['value']})
                            ->get();

                        if (isset($joinData['sortDesc'])) {
                            $join = $join->sortByDesc($joinData['sortDesc']);
                        }
                        if (isset($joinData['sort'])) {
                            $join = $join->sortBy($joinData['sort']);
                        }

                        $t->{$key} = !empty($joinData['single'])
                        ? $join->first()
                        : $join;
                    });
                }
            }

            if ($params['type'] == 'count') {
                $model->map(function ($item) use (&$results, $params) {
                    $value = $this->loadValue($params['field'], $item);
                    $label = $params['labels'][$value] ?? $value;
                    $results[$label] = ($results[$label] ?? 0) + 1;
                });
            }

            if ($params['type'] == 'value') {
                $model->map(function ($item) use (&$results, $params) {
                    $value = $this->loadValue($params['field'], $item);
                    $label = $params['labels'][$value] ?? $value;
                    $results[$label] = ($results[$label] ?? 0) + 1;
                });
            }
            if ($params['type'] == 'fields') {
                if (!empty($resultData)) {
                    $resultData = $resultData->merge($model); // = array_merge($resultData, $model->toArray());//->get($params['fields'] ?? []);

                } else {
                    $resultData = $model;
                }
                $hasData = true;
            }
        }

        unset($results['']);

        $results = [
            'data' => $resultData,
            'name' => $dataset->name,
            'labels' => array_keys($results),
            'values' => array_values($results),
        ];

        $results = $this->cacheResponse(!empty($resultData) && $hasData ? $resultData : $results);

        if (!empty($resultData) && $hasData) {
            return $this->showAll($resultData);
        }

        return $this->successResponse($results);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Dataset  $dataset
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Dataset $dataset)
    {
        $dataset->fill($request->all());

        if ($dataset->isClean()) {
            return $this->errorResponse('The values are the same', 422);
        }
        $dataset->save();

        $this->clearCache();

        return $this->showOne($dataset);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Dataset  $dataset
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dataset $dataset)
    {
        //
    }
}
