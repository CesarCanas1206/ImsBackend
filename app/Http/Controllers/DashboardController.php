<?php

namespace App\Http\Controllers;

use App\Http\Controllers\APIController;
use App\Models\Booking;

class DashboardController extends APIController
{
    public function run($type)
    {
        switch ($type) {
            case 'completed-allocations':
                $count = Booking::where('type', 'allocation')->join('collection_field', function ($join) {
                    $join->on('collection_field.collection_id', '=', 'booking.id')
                        ->where('collection_field.reference', '=', 'completed')
                        ->where('collection_field.value', '=', 'Yes');
                })->leftJoin('collection_field as approved', function ($join) {
                    $join->on('approved.collection_id', '=', 'booking.id')
                        ->where('approved.reference', '=', 'approved');
                })->where(function ($where) {
                    $where->where('approved.value', '!=', 'Yes')
                        ->orWhereNull('approved.value');
                })->count();
                break;
            case 'approved-allocations':
                $count = Booking::where('type', 'allocation')->join('collection_field', function ($join) {
                    $join->on('collection_field.collection_id', '=', 'booking.id')
                        ->where('collection_field.reference', '=', 'approved')
                        ->where('collection_field.value', '=', 'Yes');
                })->count();
                break;
            case 'completed-bookings':
                $count = Booking::where('type', '!=', 'allocation')->join('collection_field', function ($join) {
                    $join->on('collection_field.collection_id', '=', 'booking.id')
                        ->where('collection_field.reference', '=', 'completed')
                        ->where('collection_field.value', '=', 'Yes');
                })->leftJoin('collection_field as approved', function ($join) {
                    $join->on('approved.collection_id', '=', 'booking.id')
                        ->where('approved.reference', '=', 'approved');
                })->where(function ($where) {
                    $where->where('approved.value', '!=', 'Yes')
                        ->orWhereNull('approved.value');
                })->count();
                break;
            case 'approved-bookings':
                $count = Booking::where('type', '!=', 'allocation')->join('collection_field', function ($join) {
                    $join->on('collection_field.collection_id', '=', 'booking.id')
                        ->where('collection_field.reference', '=', 'approved')
                        ->where('collection_field.value', '=', 'Yes');
                })->count();
                break;
        }
        return $this->successResponse(['data' => ['count' => $count]]);
    }

}
