<?php
namespace App\Http\Controllers;

use App\Models\Calendar;
use DB;
use Illuminate\Http\Request;

class CheckController extends APIController
{
    use \App\Traits\DateFunctions;
    public function dateFilter($start, $end, $checkStart, $checkEnd)
    {
        if (
            ($start < $checkStart && $end > $checkStart) ||
            ($start < $checkStart && $end > $checkEnd) ||
            ($start > $checkStart && $start < $checkEnd && $end > $checkEnd) ||
            ($start > $checkStart && $end < $checkEnd)
        ) {
            return true;
        }
        return false;
    }

    public function dateCheck($query)
    {
        $request = request();
        // start is less than $start and end is after $start - overlaps beginning
        $query->where(function ($query) use ($request) {
            $query->where('start', '<', DB::raw("'{$request->start}'"))
                ->where('end', '>', DB::raw("'{$request->start}'"));
        });
        // start is less than $start and end is after $end - covers whole request
        $query->orWhere(function ($query) use ($request) {
            $query->where('start', '<', DB::raw("'{$request->start}'"))
                ->where('end', '>', DB::raw("'{$request->end}'"));
        });
        // start is after $start and start is before $end and end is after $end - overlaps end
        $query->orWhere(function ($query) use ($request) {
            $query->where('start', '>', DB::raw("'{$request->start}'"))
                ->where('start', '<', DB::raw("'{$request->end}'"))
                ->where('end', '>', DB::raw("'{$request->end}'"));
        });
        // start is after $start and end is before $end - overlaps within request
        $query->orWhere(function ($query) use ($request) {
            $query->where('start', '>', DB::raw("'{$request->start}'"))
                ->where('end', '<', DB::raw("'{$request->end}'"));
        });

        return $query;
    }

    public function availability(Request $request)
    {
        $collection = Calendar::select([
            'start',
            'end',
            'asset_id',
        ])->where('allow', 0);

        // $collection->join('collection_field as asset', function ($join) use ($request) {
        //     $join->on('asset.collection_id', '=', 'collection.id');
        //     $join->where('asset.reference', '=', 'asset_id');
        //     if ($request->has('asset_id')) {
        //         $join->where('asset.value', $request->asset_id);
        //     }
        // });
        // $collection->join('collection_field as title', function ($join) use ($request) {
        //     $join->on('title.collection_id', '=', 'collection.id');
        //     $join->where('title.reference', DB::raw("'title'"));
        // });
        // $collection->join('collection_field as start', function ($join) use ($request) {
        //     $join->on('start.collection_id', '=', 'collection.id');
        //     $join->where('start.reference', 'start');
        // });

        // $collection->join('collection_field as end', function ($join) use ($request) {
        //     $join->on('end.collection_id', '=', 'collection.id');
        //     $join->where('end.reference', 'end');
        //     // $join->where(function ($query) use ($request) {
        //     //     return $this->dateCheck($query);
        //     //     // $query->where('start.value', '<', DB::raw("'{$request->start}'"))
        //     //     //     ->where('end.value', '>', DB::raw("'{$request->start}'"));
        //     // });
        // });

        $collection->orderBy('created_at', 'DESC');

        // $collection = $collection->whereIn('collection.slug', ['booking', 'calendar', 'closure']);

        // $collection->limit(500);

        // $collection = $collection->where(function ($query) {
        //     return $this->dateCheck($query);
        // });
        $startDate = $request->start;
        $endDate = $request->end;
        $assets = [];

        // $collection = $collection->chunk(500, function ($rows) use ($startDate, $endDate, &$assets) {

        //     $rows->each(function ($item) use ($startDate, $endDate, &$assets) {
        //         $check = $this->dateFilter($item->start, $item->end, $startDate, $endDate);
        //         if ($check && !isset($assets[$item->asset_id])) {
        //             if (strstr($item->asset_id, ',')) {
        //                 $ids = explode(',', $item->asset_id);
        //                 foreach ($ids as $id) {
        //                     $assets[$id] = true;
        //                 }
        //                 return;
        //             }
        //             $assets[$item->asset_id] = true;
        //         }
        //     });
        // });

        $collection->each(function ($item) use ($startDate, $endDate, &$assets) {
            $check = $this->dateFilter($item->start, $item->end, $startDate, $endDate);
            if ($check && !isset($assets[$item->asset_id])) {
                if (strstr($item->asset_id, ',')) {
                    $ids = explode(',', $item->asset_id);
                    foreach ($ids as $id) {
                        $assets[$id] = true;
                    }
                    return;
                }
                $assets[$item->asset_id] = true;
            }
        });

        $collection = $collection->get();

        // $collection = $collection->chunk(100);
        // $collection = $collection->map(function ($query) {
        //     return $this->dateCheck($query);
        // });

        return $this->successResponse(['data' => array_keys($assets)]);
    }

    public function assetChildList($assetId)
    {
        $ids = [$assetId];
        $childIds = \DB::table('asset')->where('parent_id', $assetId)->pluck('id');
        if (!empty($childIds)) {
            foreach ($childIds as $childId) {
                $ids[] = $childId;
                $ids = array_merge($ids, $this->assetChildList($childId));
            }
        }
        return $ids;
    }

    public function assetList($assetId, $first = true)
    {
        $ids = [$assetId];
        $parentIds = \DB::table('asset')->where('id', $assetId)->pluck('parent_id');
        if (!empty($parentIds)) {
            foreach ($parentIds as $parentId) {
                if ($parentId) {
                    $ids[] = $parentId;
                    $ids = array_merge($ids, $this->assetList($parentId, false));
                }
            }
        }
        if ($first) {
            $ids = array_merge($ids, $this->assetChildList($assetId));
        }

        return array_values(array_unique($ids));
    }

    public function clash(Request $request)
    {
        $item = $request;
        if ($request->has('repeating') && $request->repeating == 'Yes') {
            $dates = $this->datesFromPattern(
                $item->pattern,
                $item->date ?? '',
                $item->end_date ?? '',
                $item->day ?? '',
                $item->times ?? '',
                !empty($item->exclude_dates) ? json_decode($item->exclude_dates, true) : []
            );
        }
        $collection = Calendar::select([
            'id',
            'start',
            'end',
            'asset_id',
        ])->where('allow', 0);

        if ($request->has('id')) {
            $collection->where('id', '!=', $request->id);
        }
        if ($request->has('asset_id')) {
            $assetList = $this->assetList($request->asset_id);
            $collection->whereIn('asset_id', $assetList);
        }

        if (empty($dates)) {
            $dates = [$request->date];
        }

        $collection = $collection->where(function ($query) use ($dates, $request) {
            foreach ($dates as $date) {
                $start = $date . ' ' . $request->start;
                $end = $date . ' ' . $request->end;

                // start is less than $start and end is after $start - overlaps beginning
                $query->where(function ($query) use ($start, $end) {
                    $query->where('start', '<', DB::raw("'{$start}'"))
                        ->where('end', '>', DB::raw("'{$start}'"));
                });
                // start is less than $start and end is after $end - covers whole request
                $query->orWhere(function ($query) use ($start, $end) {
                    $query->where('start', '<', DB::raw("'{$start}'"))
                        ->where('end', '>', DB::raw("'{$end}'"));
                });
                // start is after $start and start is before $end and end is after $end - overlaps end
                $query->orWhere(function ($query) use ($start, $end) {
                    $query->where('start', '>', DB::raw("'{$start}'"))
                        ->where('start', '<', DB::raw("'{$end}'"))
                        ->where('end', '>', DB::raw("'{$end}'"));
                });
                // start is after $start and end is before $end - overlaps within request
                $query->orWhere(function ($query) use ($start, $end) {
                    $query->where('start', '>', DB::raw("'{$start}'"))
                        ->where('end', '<', DB::raw("'{$end}'"));
                });
            }
        });

        $collection = $collection->get();

        if (!$request->has('asset_id')) {
            $assets = [];
            $collection->each(function ($item) use (&$assets) {
                $assets[] = $item->asset_id;
            });
            $collection = array_values(array_unique($assets));
        }

        return $this->successResponse(['data' => $collection]);
    }
}
