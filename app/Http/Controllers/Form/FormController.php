<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\APIController;
use App\Models\CollectionField;
use App\Models\Form;
use App\Models\FormQuestion;
use Illuminate\Http\Request;

class FormController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Form $form)
    {
        request()->request->add(['sort_by' => 'name']);
        if (request()->has('with')) {
            $results = form::with(explode(',', request()->with));
        } else {
            $results = new Form();
        }

        $results = $results->get();
        $results = $results->map([$this, 'mapFieldsToValues']);
        return $this->showAll($results);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $order = 1;
        $form = Form::create($request->all());
        if (!empty($request->questions)) {
            foreach ($request->questions as $item) {
                $item['form_id'] = $form->id;
                if (isset($item['props'])) {
                    $item['props'] = !is_array($item['props']) ? (json_decode($item['props'], true) ?? []) : $item['props'];
                }
                $item['question_order'] = $item['question_order'] ?? $order;
                if (isset($item['form_props'])) {
                    $item['form_props'] = !is_array($item['form_props']) ? (json_decode($item['form_props'], true) ?? []) : $item['form_props'];
                }
                FormQuestion::create($item);
                $order++;
            }
        }
        $this->addResponses($request, $form->id, Form::class);
        $this->clearCache();
        return $this->show($form);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Form  $form
     * @return \Illuminate\Http\Response
     */
    public function show(Form $form)
    {
        $result = $this->mapFieldsToValues($form);
        return $this->showOne($result);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Form  $form
     * @return \Illuminate\Http\Response
     */
    public function data(Form $form)
    {
        $form = Form::with('questions')
            ->select(['id', 'endpoint', 'reference', 'props'])
            ->findOrFail($form->id);
        return $this->showOne($form);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Form  $form
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Form $form)
    {

        $form->fill($request->all())->save();

        $this->addResponses($request, $form->id, Form::class);
        $this->clearCache();
        return $this->show($form);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Form  $form
     * @return \Illuminate\Http\Response
     */
    public function destroy(Form $form)
    {

        $form->delete();

        CollectionField::where('collection_id', $form->id)->delete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $form->id]);
    }
}
