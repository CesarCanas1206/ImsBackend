<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\CollectionField;
use App\Models\Usage;
use Illuminate\Http\Request;

class UsageController extends APIController
{
    use \App\Traits\DateFunctions;

    public function index()
    {
        $results = new Usage();

        $checks = ['parent_id', 'id'];
        foreach ($checks as $check) {
            if (request()->has($check) && !in_array(request()->{$check}, ['empty', '!empty'])) {
                $value = explode(',', str_replace(['!', '[', ']'], '', request()->{$check}));
                $results = $results->whereIn($check, $value);
            }
        }

        $results = $results->get();

        $results = $results->map([$this, 'mapFurther']);
        $results = $results->map([$this, 'mapFieldsToValues']);
        return $this->showAll($results);
    }

    public function show($id)
    {
        return $this->showOne(Usage::where('id', $id)->get()->map([$this, 'mapFurther'])->map([$this, 'mapFieldsToValues'])->first());
    }

    public function mapFurther($collection)
    {
        if (!empty($collection->asset)) {
            $collection->asset = $this->mapFieldsToValues($collection->asset);
        }
        // if (!empty($collection->parent_id)) {
        //     $collection->hirer = Hirer::join('booking', 'booking.hirer_id', '=', 'hirer.id')->where('booking.id', $collection->parent_id)->get()->map([$this, 'mapFieldsToValues'])->first();
        // }
        return $collection;
    }

    public function store(Request $request)
    {
        $new = Usage::create($request->all());
        $this->addResponses($request, $new->id, Usage::class);

        $approved = CollectionField::where('collection_id', $new->parent_id)
            ->where('reference', 'approved')
            ->pluck('value')
            ->first();

        $pending = empty($approved) || $approved !== 'Yes';
        $this->buildCalendar($new->id, $pending);

        $this->clearCache();
        return $this->show($new->id);
    }

    public function update(Request $request, Usage $usage)
    {
        $usage->fill($request->all())->save();

        $this->addResponses($request, $usage->id, Usage::class);

        $approved = CollectionField::where('collection_id', $usage->parent_id)
            ->where('reference', 'approved')
            ->pluck('value')
            ->first();

        $pending = empty($approved) || $approved !== 'Yes';
        $this->buildCalendar($usage->id, $pending);

        $this->clearCache();
        return $this->show($usage->id);
    }

    public function destroy(Request $request, Usage $usage)
    {
        $usage->delete();

        CollectionField::where('collection_id', $usage->id)->delete();
        Calendar::where('usage_id', $usage->id)->delete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $usage->id]);
    }

    public function bookingUsageFees($id)
    {
        $ids = Usage::where('parent_id', $id)->pluck('asset_id');
        //get pricing_id from collectionField
        $pricingIds = CollectionField::whereIn('collection_id', $ids)
            ->where('reference', 'pricing_id')
            ->get(['collection_id', 'value'])
            ->toArray();

        $getRates = CollectionField::whereIn('collection_id', array_column($pricingIds, 'value'))
            ->where('reference', 'rates')
            ->get(['collection_id', 'value'])
            ->toArray();

        $rates = [];
        foreach ($getRates as $rate) {
            $rates[$rate['collection_id']] = $rate['value'];
        }

        $results = [];
        foreach ($pricingIds as $pricingId) {
            if (!isset($rates[$pricingId['value']])) {
                continue;
            }
            $results[$pricingId['collection_id']] = json_decode($rates[$pricingId['value']], true);
        }

        return $this->successResponse(['data' => $results]);
    }

    /**
     * Build the usage calendar items
     */
    public function buildCalendar($id, $pending = false)
    {
        $pending = $pending ? 1 : 0;
        $slug = $pending ? 'calendar-pending' : 'calendar';

        $item = $this->mapFieldsToValues(Usage::where('id', $id)->first());

        Calendar::where('usage_id', $item->id)->delete();

        /** If no time is set then skip */
        if (empty($item->start)) {
            return [];
        }

        /** Get dates from repeating pattern if repeating, otherwise use given date */
        $dates = [$item->date];
        if (!empty($item->repeating) && $item->repeating === 'Yes') {
            $dates = $this->datesFromPattern(
                $item->pattern,
                $item->date ?? '',
                $item->end_date ?? '',
                $item->day ?? '',
                $item->times ?? '',
                !empty($item->exclude_dates) ? json_decode($item->exclude_dates, true) : []
            );
        }

        $items = [];
        /** Build the calendar items for each date */
        foreach ($dates as $date) {
            $start = $date . ' ' . $item->start;
            $end = $date . ' ' . $item->end;

            $event = [
                'usage_id' => $item->id,
                'pending' => $pending,
                'slug' => $slug,
                'start' => $start,
                'end' => $end,
                'asset_id' => $item->asset_id,
                'title' => $item->activity ?? $item->title,
                'parent_id' => $item->parent_id,
            ];

            $items[] = $event;
        }

        /** Create the calendar items */
        foreach ($items as $item) {
            Calendar::create(
                $item
            );
        }

        return $items;
    }
}
