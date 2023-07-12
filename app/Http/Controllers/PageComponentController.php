<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageComponent;
use Illuminate\Http\Request;

class PageComponentController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Page $page)
    {
        if (isset($request->components)) {
            $order = 1;
            foreach ($request->components as $component) {
                $component['page_id'] = $request->page_id;
                $component['parent_id'] = $request->parent_id ?? '';
                $component['order'] = $order;
                $pageComponent = PageComponent::create($component);
                $order++;
                if (isset($component['sub'])) {
                    $parentId = $pageComponent->id;
                    foreach ($component['sub'] as $sub) {
                        $order++;
                        $sub['parent_id'] = $parentId;
                        $sub['page_id'] = $request->page_id;
                        $sub['order'] = $order;
                        $pageComponent = PageComponent::create($sub);
                        if (isset($sub['sub'])) {
                            $parentId2 = $pageComponent->id;
                            foreach ($sub['sub'] as $sub2) {
                                $order++;
                                $sub2['parent_id'] = $parentId2;
                                $sub2['page_id'] = $request->page_id;
                                $sub2['order'] = $order;
                                $pageComponent = PageComponent::create($sub2);
                                if (isset($sub2['sub'])) {
                                    $parentId3 = $pageComponent->id;
                                    foreach ($sub2['sub'] as $sub3) {
                                        $order++;
                                        $sub3['parent_id'] = $parentId3;
                                        $sub3['page_id'] = $request->page_id;
                                        $sub3['order'] = $order;
                                        PageComponent::create($sub3);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $this->successResponse(['data' => 'Created multiple components'], 201);
        }
        $rules = [
            'component' => 'required',
        ];

        $this->validate($request, $rules);

        $pageComponent = PageComponent::create($request->all());

        $this->clearCache();

        return $this->showOne($pageComponent, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PageComponent  $pageComponent
     * @return \Illuminate\Http\Response
     */
    public function show(PageComponent $pageComponent)
    {
        return $this->showOne($pageComponent);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PageComponent  $pageComponent
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PageComponent $pageComponent)
    {
        $pageComponent->fill($request->all());

        if ($pageComponent->isClean()) {
            return $this->errorResponse('The values are the same', 422);
        }
        $pageComponent->save();

        $this->clearCache();

        return $this->showOne($pageComponent);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PageComponent  $pageComponent
     * @return \Illuminate\Http\Response
     */
    public function destroy(PageComponent $pageComponent)
    {
        $pageComponent->delete();

        return $this->successResponse(['data' => 'Deleted'], 200);
    }
}
