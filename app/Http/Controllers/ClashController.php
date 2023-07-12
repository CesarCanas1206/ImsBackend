<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Booking;
use App\Models\Calendar;
use App\Models\Hirer;

class ClashController extends APIController
{
    public function checkClash($one, $two)
    {
        $clash = ($one['start'] <= $two['start'] && $one['end'] > $two['start']) ||
            ($one['start'] < $two['end'] && $one['end'] >= $two['end']) ||
            ($one['start'] >= $two['start'] && $one['end'] <= $two['end']);

        return $clash;
    }

    /**
     * Load the current clashes
     */
    public function data()
    {
        // $cached = $this->hasCache();
        // if ($cached) {
        //     return $this->successResponse(['data' => $cached]);
        // }

        // Compare the calendar and calendar-pending collections
        // Check for clashes where the start/end overlaps in some way to the other start/end
        $calendar = Calendar::where('allow', 0)->get()->toArray();

        if (empty($calendar)) {
            return $this->successResponse(['data' => []]);
        }

        $parentIds = [];
        $assetItems = [];
        foreach ($calendar as $key => $calendarItem) {
            if (empty($calendarItem['start']) || empty($calendarItem['asset_id'])) {
                unset($calendar[$key]);
            }

            if (!isset($assetItems[$calendarItem['asset_id']])) {
                $assetItems[$calendarItem['asset_id']] = [];
            }
            $parentIds[$calendarItem['parent_id']] = '';
            $assetItems[$calendarItem['asset_id']][] = $calendarItem;
        }

        $parentIds = array_keys($parentIds);

        $hirerIds = [];

        $assetIds = array_keys($assetItems);
        Asset::whereIn('id', $assetIds)
            ->get()
            ->each(function ($asset) use (&$assetItems) {
                $this->tmpLoaded[$asset->id] = $asset->name;
            });

        $this->tmpLoader['hirers'] = [];
        $hirerIds = [];

        Booking::whereIn('id', $parentIds)
            ->get()
            ->each(function ($item) use (&$hirerIds) {
                $hirerIds[$item->id] = $item->hirer_id;
                $this->tmpLoaded['hirers'][$item->id] = $item->hirer_id;
            });

        Hirer::whereIn('id', $hirerIds)
            ->get()
            ->each(function ($item) use (&$hirers) {
                $hirers[$item->id] = $item->name;
            });

        unset($calendar);

        $clashes = [];
        // --- Clash check in here
        foreach ($assetItems as $asset_id => $calendarItems) {
            foreach ($calendarItems as $calendarItem) {
                if (empty($calendarItem['start'])) {continue;}
                if (empty($calendarItem['asset_id'])) {continue;}
                $date = new \DateTime($calendarItem['start']);
                $key = $calendarItem['asset_id'] . $date->format('Y-m-d');

                if (isset($clashes[$key])) {
                    continue;
                }
                $itemClashes = [];

                foreach ($calendarItems as $calendarPendingItem) {
                    if (empty($calendarPendingItem['start'])) {continue;}
                    if (empty($calendarPendingItem['asset_id'])) {continue;}
                    if ($calendarItem['asset_id'] != $calendarPendingItem['asset_id']) {continue;}
                    if ($calendarItem['id'] == $calendarPendingItem['id']) {continue;}

                    if (
                        $this->checkClash($calendarItem, $calendarPendingItem)
                    ) {
                        $hirer = $hirers[$this->tmpLoaded['hirers'][$calendarPendingItem['parent_id']] ?? ''] ?? '';
                        $itemClashes[] = [
                            'pending' => $calendarPendingItem['slug'] == 'calendar-pending',
                            'id' => $calendarPendingItem['id'] ?? '',
                            'usage_id' => $calendarPendingItem['usage_id'] ?? '',
                            'parent_id' => $calendarPendingItem['parent_id'] ?? '',
                            'hirer' => $hirer ?? '',
                            'title' => $calendarPendingItem['title'] ?? $calendarPendingItem['activity'] ?? '',
                            'start' => $calendarPendingItem['start'] ?? '',
                            'end' => $calendarPendingItem['end'] ?? '',
                        ];
                    }

                }

                if (empty($itemClashes)) {continue;}

                $hirer = $hirers[$this->tmpLoaded['hirers'][$calendarItem['parent_id']] ?? ''] ?? '';

                $clashes[$key] = [
                    'asset_id' => $calendarItem['asset_id'] ?? '',
                    'asset' => $this->tmpLoaded[$calendarItem['asset_id']] ?? '',
                    'date' => $date->format('Y-m-d'),
                    'clashes' => array_merge(
                        [[
                            'pending' => $calendarItem['slug'] == 'calendar-pending',
                            'id' => $calendarItem['id'] ?? '',
                            'usage_id' => $calendarItem['usage_id'] ?? '',
                            'parent_id' => $calendarItem['parent_id'] ?? '',
                            'hirer' => $hirer ?? '',
                            'title' => $calendarItem['title'] ?? $calendarItem['activity'] ?? '',
                            'start' => $calendarItem['start'] ?? '',
                            'end' => $calendarItem['end'] ?? '',
                        ]],
                        $itemClashes,
                    ),
                ];
            }

        }

        return $this->successResponse(['data' => array_values($clashes)]);

        $result = $this->cacheResponse($clashes);

        // $result = cache()->remember($cacheKey, 60 * 60, function () use ($clashes) {
        //     return $clashes;
        // });

        return $this->successResponse(['data' => $result]);
    }
}
