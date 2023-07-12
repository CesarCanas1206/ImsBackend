<?php

namespace App\Http\Controllers;

use App\Models\CollectionHistory;
use DB;
use Illuminate\Http\Request;

class CollectionHistoryController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(CollectionHistory $collectionHistory)
    {
        $history = CollectionHistory::limit(10)->get();
        return $this->showAll($history);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $check = ['collection_id' => $request->inspection_id, 'reference' => $request->reference];
        $new = CollectionHistory::updateOrCreate($check, $request->all());

        $this->clearCache();

        return $this->showOne($new, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CollectionHistory  $collectionHistory
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $fields = [];
        CollectionHistory::
            where('collection_id', DB::raw("'{$id}'"))
            ->orderBy('collection_history.created_at', 'desc')
            ->select(['collection_history.*', 'users.name as created_by_name'])
            ->leftJoin('users', 'users.id', 'collection_history.created_by')
            ->where('collection_history.created_at', '<=', DB::raw("'" . ($request->date ?? date('Y-m-d')) . " 23:59:59'"))
            ->get()
            ->each(function ($item) use (&$fields) {
                if (isset($fields[$item->reference])) {
                    return;
                }
                $fields[$item->reference] = $item;
            });

        return $this->successResponse(['data' => $fields]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CollectionHistory  $collectionHistory
     * @return \Illuminate\Http\Response
     */
    public function getLog($id, Request $request)
    {
        $fields = CollectionHistory::
            where('collection_id', DB::raw("'{$id}'"))
            ->orderBy('collection_history.created_at', 'desc')
            ->select(['collection_history.*', 'users.name as created_by_name'])
            ->leftJoin('users', 'users.id', 'collection_history.created_by')
            ->limit(100)
            ->get();

        return $this->successResponse(['data' => $fields]);
    }

    public function getDates($id)
    {
        $history = CollectionHistory::
            where('collection_id', DB::raw("'{$id}'"))
            ->select(DB::raw("distinct(date(created_at)) as created_at"))
            ->orderBy('created_at', 'desc')
            ->pluck('created_at');

        return $this->successResponse(['data' => $history]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CollectionHistory  $collectionHistory
     * @return \Illuminate\Http\Response
     */
    public function update(CollectionHistory $collectionHistory, Request $request)
    {
        $collectionHistory->fill($request->all());

        $collectionHistory->save();

        $this->clearCache();

        return $this->showOne($collectionHistory);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CollectionHistory  $collectionHistory
     * @return \Illuminate\Http\Response
     */
    public function destroy(CollectionHistory $collectionHistory)
    {
        //
    }
}
