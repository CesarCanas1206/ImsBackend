<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Hash;
use App\Models\Feed;
use App\Models\Calendar;
class FeedController extends APIController
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $data = Calendar::with('role')
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'slug',
                'pending',
                'allow',
            ]);

        return $this->showAll($data);
    }
} 