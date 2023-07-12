<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use Illuminate\Http\Request;

class FeeController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $fees = Fee::orderBy('created_at', 'asc')->get()->map(function ($fee) {
            if (empty($fee->total)) {
                $fee->total = $fee->unit * $fee->rate;
            }
            return $fee;
        });

        return $this->showAll($fees);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $new = Fee::create($request->all());
        return $this->showOne($new);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Fee  $fee
     * @return \Illuminate\Http\Response
     */
    public function show(Fee $fee)
    {
        return $this->showOne($fee);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Fee  $fee
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Fee $fee)
    {
        $fee->fill($request->all())->save();
        $updatedTotal = $fee->unit * $fee->rate;
        if ($updatedTotal != $fee->total) {
            $fee->fill(['total' => $updatedTotal])->save();
        }
        return $this->showOne($fee);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Fee  $fee
     * @return \Illuminate\Http\Response
     */
    public function destroy(Fee $fee)
    {
        $fee->delete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $fee->id]);
    }

    public function destroyByBookingId($bookingId)
    {
        Fee::where('booking_id', $bookingId)->delete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $bookingId]);
    }

}
