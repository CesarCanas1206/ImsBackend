<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\APIController;
use App\Models\Form;
use App\Models\FormQuestion;
use Illuminate\Http\Request;

class FormQuestionController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(FormQuestion $formQuestion, Form $form)
    {
        return $this->showAll($form->questions->sortBy('question_order'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $all = $request->all();
        if (isset($all['props'])) {
            $all['props'] = !is_array($all['props']) ? (json_decode($all['props'], true) ?? []) : $all['props'];
        }
        if (isset($all['form_props'])) {
            $all['form_props'] = !is_array($all['form_props']) ? (json_decode($all['form_props'], true) ?? []) : $all['form_props'];
        }
        $formQuestion = FormQuestion::create($all);
        $this->clearCache();
        return $this->showOne($formQuestion);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function show(Form $form, FormQuestion $formQuestion)
    {
        return $this->showOne($formQuestion);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FormQuestion $formQuestion)
    {
        $all = $request->all();
        if (isset($all['props'])) {
            $all['props'] = !is_array($all['props']) ? (json_decode($all['props'], true) ?? []) : $all['props'];
        }
        if (isset($all['form_props'])) {
            $all['form_props'] = !is_array($all['form_props']) ? (json_decode($all['form_props'], true) ?? []) : $all['form_props'];
        }
        $formQuestion->fill($all);
        $formQuestion->save();
        $this->clearCache();

        return $this->showOne($formQuestion);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Question  $question
     * @return \Illuminate\Http\Response
     */
    public function destroy(FormQuestion $question)
    {
        $question->delete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $question->id]);
    }
}
