<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Traits\AzureBucketApi as AzureBucketApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
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
    public function store($file)
    {
        $dir = Request()->header('x-site-name') . '/';
        $data = substr($file, strpos($file, ',') + 1);
        $data = base64_decode($data);
        $extension = explode('/', mime_content_type($file))[1];
        $fileName = time() . round(rand(0, 99)) . '.' . $extension;
        $path = $dir . $fileName;

        $azure = new AzureBucketApi();
        $azure->setContainer(Request()->header('x-site-name'));
        $azure->uploadFile($data, $fileName);

        $url = env('APP_URL') . Storage::url($path);
        File::create([
            'name' => $fileName,
            'path' => $url,
        ]);
        return json_encode([['name' => $fileName, 'path' => $url]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request)
    {
        $dir = Request()->header('x-site-name') . '/';
        $data = [
            'file' => $request->file('file')->getRealPath(),
            'name' => $request->file('file')->getClientOriginalName(),
            'ext' => $request->file('file')->getClientOriginalExtension(),
        ];
        $fileName = time() . round(rand(0, 99)) . '.' . $data['ext'];
        $path = $dir . $fileName;

        $azure = new AzureBucketApi();
        $azure->setContainer(Request()->header('x-site-name'));
        $azure->uploadFile($data['file'], $fileName);

        $url = env('APP_URL') . Storage::url($path);

        File::create([
            'name' => $fileName,
            'path' => $url,
        ]);
        return json_encode(['data' => ['name' => $fileName, 'path' => $url]]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
