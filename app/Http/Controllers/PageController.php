<?php

namespace App\Http\Controllers;

use App\Http\Controllers\APIController;
use App\Models\Page;
use App\Models\PageComponent;
use Illuminate\Http\Request;

class PageController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->showAll(
            Page::orderBy('order')
                ->select(['id', 'name', 'path', 'icon', 'public', 'show', 'order', 'parent_id'])
                ->get()
        );
    }

    public function publicPages()
    {
        return $this->showAll(Page::where('public', 1)->orderBy('order')->get());
    }

    public function pageComponents(Page $page)
    {
        return $this->showAll(
            PageComponent::whereIn('page_id', [$page->id, -1])
                ->select(['id', 'parent_id', 'order', 'component', 'props'])
                ->orderBy('order')
                ->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
        ];

        $this->validate($request, $rules);

        $data = $request->all();

        if (isset($data['path'])) {
            $data['path'] = trim($data['path'], '/');
        }

        $page = Page::create($data);

        $this->clearCache();

        return $this->showOne($page, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function show(Page $page)
    {
        $page = Page::with('components')->findOrFail($page->id);

        return $this->showOne($page);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Page  $page
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Page $page)
    {
        $data = $request->all();

        if (isset($data['path'])) {
            $data['path'] = trim($data['path'], '/');
        }

        $page->fill($data);

        if ($page->isClean()) {
            return $this->errorResponse('The values are the same', 422);
        }
        $page->save();

        $this->clearCache();

        $page = Page::with('components')->findOrFail($page->id);

        return $this->showOne($page);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Activity\Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function destroy(Page $page)
    {
        //Delete new added page
        Page::destroy($page->id);
        return response()->json(['data' => 'Deleted', $page->id]);
    }
}
