<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\Form;
use App\Models\Page;

class InitialiseController extends APIController
{
    /**
     * Load the initial data for the program
     */
    public function load($type = null)
    {
        $results = ['pages' => []];
        Config::where('public', '1')
            ->orWhereIn('code', ['logo', 'modules', 'theme'])
            ->each(function ($item) use (&$results) {
                $value = stripslashes($item->value);
                $results['settings'][$item->code ?: $item->name] = substr($value, 0, 2) === '{"' ||
                substr($value, 0, 2) === '[{' ||
                substr($value, 0, 2) === '["'
                ? json_decode(stripslashes($value), true)
                : $value;
            });

        Form::select(['id', 'reference'])
            ->each(function ($item) use (&$results) {
                $results['forms'][] = $item;
            });

        $getPages = Page::select(['id', 'parent_id', 'icon', 'name', 'path', 'show', 'module', 'permission', 'order']);
        // if (request()->user() === null) {
        //     $getPages->where('public', 1);
        // }
        $getPages->each(function ($item) use (&$results) {
            $results['pages'][] = array_filter($item->toArray());
        });
        return $this->successResponse(['data' => $results]);
    }
}
