<?php
namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Usage;
use Illuminate\Http\Request;

class CalendarController extends APIController
{
    public function index()
    {
        return $this->calendar();
    }

    public function show($id)
    {
        return $this->showOne(Calendar::leftJoin('collection_field', function ($join) {
            $join->on('collection_field.collection_id', '=', 'calendar.id')
                ->where('collection_field.reference', '=', 'allday');
        })
                ->where('calendar.id', $id)
                ->select(['collection_field.value as allday', 'calendar.*'])
                ->first());
    }

    public function store(Request $request)
    {
        if (!$request->has('slug')) {
            $request->merge(['slug' => $request->form_id ?? 'calendar']);
        }
        $new = Calendar::create($request->all());
        $this->addResponses($request, $new->id, Calendar::class);
        $this->clearCache();
        return $this->show($new->id);
    }

    public function update(Request $request, Calendar $calendar)
    {
        if (!$request->has('slug')) {
            $request->merge(['slug' => $request->form_id ?? 'calendar']);
        }
        $calendar->fill($request->all())->save();

        $this->addResponses($request, $calendar->id, Calendar::class);

        $this->clearCache();
        return $this->show($calendar->id);
    }

    public function allowMultiple()
    {
        $items = json_decode(request()->clash_ids, true);

        Calendar::whereIn('id', $items)->update(['allow' => 1]);

        return $this->successResponse(['data' => 'Updated']);
    }

    // public function buildAssetTree($assets, $parentId = null)
    // {
    //     $tree = array();
    //     foreach ($assets as $asset) {
    //         if ($asset->parent_id == $parentId) {
    //             $children = $this->buildAssetTree($assets, $asset->id);
    //             if (!empty($children)) {
    //                 $asset->children = $children;
    //             }
    //             $tree[$asset->id] = $asset;
    //         }
    //     }
    //     return $tree;
    // }

    public function getAssetChildren($assets, $parentId)
    {
        $children = [];

        foreach ($assets as $asset) {
            if ($asset->parent_id === $parentId) {
                $childId = $asset->id;
                $grandChildren = $this->getAssetChildren($assets, $childId);
                $children[] = $childId;

                if (!empty($grandChildren)) {
                    foreach ($grandChildren as $grandChildId => $grandChild) {
                        if (!empty($grandChildId)) {
                            $children[] = $grandChildId;
                        }
                    }
                }
            }
        }

        return $children;
    }

    public function generateAssetHierarchy($assets)
    {
        $hierarchy = [];

        foreach ($assets as $asset) {
            $assetId = $asset->id;
            $children = $this->getAssetChildren($assets, $assetId);

            if (!empty($children)) {
                $hierarchy[$assetId] = $children;
            }
        }

        return $hierarchy;
    }

    public function calendar()
    {
        $allAssets = [];
        $assets = \DB::table('asset')->whereNull('deleted_at')->get(['id', 'parent_id', 'name'])->toArray();
        $assetsWithChildIds = $this->generateAssetHierarchy($assets);

        // Map assets to array with id as key
        foreach ($assets as $asset) {
            $allAssets[$asset->id] = $asset;
        }

        $collection = Calendar::whereNull('calendar.deleted_at')
            ->leftJoin('collection_field', function ($join) {
                $join->on('collection_field.collection_id', '=', 'calendar.id')
                    ->where('collection_field.reference', '=', 'allday');
            })
            ->select(['collection_field.value as allday', 'calendar.*']);

        if (request()->has('form_id')) {
            $collection->where('form_id', request()->form_id);
        }
        if (request()->has('range')) {
            [$start, $end] = explode(',', request()->range);
            $start = $start . ' 00:00:00';
            $end = $end . ' 23:59:59';

            $collection = $collection->where(function ($query) use ($start, $end) {
                // start is less than $start and end is after $start - overlaps beginning
                $query->where(function ($query) use ($start) {
                    $query->where('start', '<', \DB::raw("'{$start}'"))
                        ->where('end', '>', \DB::raw("'{$start}'"));
                });
                // start is less than $start and end is after $end - covers whole request
                $query->orWhere(function ($query) use ($start, $end) {
                    $query->where('start', '<', \DB::raw("'{$start}'"))
                        ->where('end', '>', \DB::raw("'{$end}'"));
                });
                // start is after $start and start is before $end and end is after $end - overlaps end
                $query->orWhere(function ($query) use ($start, $end) {
                    $query->where('start', '>', \DB::raw("'{$start}'"))
                        ->where('start', '<', \DB::raw("'{$end}'"))
                        ->where('end', '>', \DB::raw("'{$end}'"));
                });
                // start is after $start and end is before $end - overlaps within request
                $query->orWhere(function ($query) use ($start, $end) {
                    $query->where('start', '>', \DB::raw("'{$start}'"))
                        ->where('end', '<', \DB::raw("'{$end}'"));
                });
            });
        }

        $collection = $collection->get();

        $collection = $collection->toArray();

        $results = [];
        foreach ($collection as $item) {
            $assetId = $item['asset_id'];
            if (request()->has('form_id')) {
                if (isset($allAssets[$assetId])) {
                    if (!empty($allAssets[$assetId]->parent_id)) {
                        $item['name'] = (new AssetController)->getParentName($allAssets[$assetId]->parent_id, $allAssets) . ' - ' . ($allAssets[$assetId]->name ?? null);
                    } else {
                        $item['name'] = ($allAssets[$assetId]->name ?? null);
                    }
                }
                $subasset = $item;
                if (isset($assetsWithChildIds[$assetId])) {
                    foreach ($assetsWithChildIds[$assetId] as $childId) {
                        $subasset['asset_id'] = $childId;
                        if (isset($allAssets[$childId])) {
                            if (!empty($allAssets[$childId]->parent_id)) {
                                $subasset['name'] = (new AssetController)->getParentName($allAssets[$childId]->parent_id, $allAssets) . ' - ' . $allAssets[$childId]->name;
                            } else {
                                $subasset['name'] = $allAssets[$childId]->name;
                            }
                        }
                        $item['sub'][] = $subasset;
                    }
                }
                $results[] = (array) $item;
            } else {
                $results[] = (array) $item;
                if (isset($assetsWithChildIds[$assetId])) {
                    foreach ($assetsWithChildIds[$assetId] as $childId) {
                        $item['asset_id'] = $childId;
                        $results[] = $item;
                    }
                }
            }
        }

        return $this->successResponse(['data' => $results]);
    }

    public function clearEvents($id)
    {
        Calendar::where('parent_id', $id)->delete();
    }

    public function buildCalendarUsage($id)
    {
        $pending = request()->has('pending') ? 1 : 0;
        $slug = $pending ? 'calendar-pending' : 'calendar';

        $usage = Usage::where('parent_id', \DB::raw("'" . $id . "'"))
            ->get()
            ->map([$this, 'mapFieldsToValues']);

        if (empty($usage->count())) {
            return $this->successResponse(['data' => []]);
        }

        $this->clearEvents($id);
        $this->setupCollectionRef($slug);

        $items = [];
        $usage->each(function ($item) use (&$items, $slug, $pending) {
            if (empty($item->start)) {
                return;
            }
            if (isset($item->start_date)) {
                $item->date = $item->start_date;
            }
            if (!isset($item->date) && isset($item->end_date)) {
                $item->date = $item->end_date;
            }
            if (!empty($item->end_date) && !strstr($item->start, '-')) {
                $item->start = $item->date . ' ' . $item->start;
                $item->end = $item->end_date . ' ' . $item->end;
            } elseif (!empty($item->date) && !strstr($item->start, '-')) {
                $item->start = $item->date . ' ' . $item->start;
                $item->end = $item->date . ' ' . $item->end;
            }

            if (!empty($item->repeating)) {
                $startTime = new \DateTime($item->start);
                $startTime = $startTime->format('H:i');
                $endTime = new \DateTime($item->end);
                $endTime = $endTime->format('H:i');
                $startDate = new \DateTime($item->date);
                $endDate = new \DateTime($item->end_date);
                $day = $item->day;
                while ($startDate->format('Y-m-d') <= $endDate->format('Y-m-d')) {
                    if ($startDate->format('l') == $day) {
                        $event = [
                            'usage_id' => $item->id,
                            'pending' => $pending,
                            'slug' => $slug,
                            'start' => $startDate->format('Y-m-d') . ' ' . $startTime,
                            'end' => $startDate->format('Y-m-d') . ' ' . $endTime,
                            'asset_id' => $item->asset_id,
                            'title' => $item->title ?? $item->activity,
                            'parent_id' => $item->parent_id,
                        ];

                        $items[] = $event;
                    }
                    $startDate->modify('+1 day');
                }
            } else {
                $event = [
                    'usage_id' => $item->id,
                    'pending' => $pending,
                    'slug' => $slug,
                    'start' => $item->start,
                    'end' => $item->end,
                    'asset_id' => $item->asset_id,
                    'title' => $item->title ?? $item->activity,
                    'parent_id' => $item->parent_id,
                ];

                $items[] = $event;
            }
        });

        $added = 0;
        foreach ($items as $item) {
            $added++;
            Calendar::create(
                $item
            );
        }

        $this->clearCache();

        return $this->successResponse(['data' => ['added ' . $added . ' items', 'items' => $items]]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Calendar  $calendar
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Calendar $calendar)
    {
        $calendar->delete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $calendar->id]);
    }
}
