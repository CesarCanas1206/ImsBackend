<?php
namespace App\Http\Controllers;

use App\Http\Controllers\APIController;
use App\Models\Config;
use Illuminate\Http\Request;

class ConfigController extends APIController
{
    function list() {
        Config::where('public', '1')
            ->orWhereIn('code', ['logo', 'modules', 'theme'])
            ->each(function ($item) use (&$results) {
                $results[$item->code ?: $item->name] = $item->value;
            });
        return $this->successResponse(['data' => $results]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->showAll(Config::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->has('value') && is_array($request->value)) {
            $request->merge(['value' => json_encode($request->value)]);
        }
        $new = Config::create($request->all());

        $this->clearCache();

        return $this->showOne($new);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Config  $config
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->showOne(config::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Config  $config
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if ($request->has('value') && is_array($request->value)) {
            $request->merge(['value' => json_encode($request->value)]);
        }
        $config = Config::findOrFail($id);
        $config->fill($request->all())->save();

        $this->clearCache();

        return $this->showOne($config);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Config::find($id)->forcedelete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted ' . $id]);
    }
}
