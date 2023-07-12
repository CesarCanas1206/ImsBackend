<?php

namespace App\Http\Controllers;

use App\Models\Collection;

class ChartController extends APIController
{
    /**
     * Load the data for the given chart
     */
    public function data($type)
    {
        $type = 'maintenance';

        $items = Collection::where('slug', $type)->get();
        $items = $items->map([$this, 'mapFieldsToValues']);

        $data = ['Complete' => $items->filter(function ($item) {return !empty($item->completed) && $item->completed == 'Yes';})->count(),
            'Overdue' => $items->filter(function ($item) {return (empty($item->completed) || $item->completed == 'No') && !empty($item->due_date) && strtotime($item->due_date) < time();})->count(),
            'Current' => $items->filter(function ($item) {return empty($item->completed) || $item->completed == 'No';})->count()];

        $results = [
            'labels' => array_keys($data),
            'data' => array_values($data),
        ];

        return $this->successResponse(['data' => $results]);
    }
}
