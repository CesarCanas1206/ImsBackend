<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF;

class PdfController extends APIController
{
    public function index(Request $request)
    {
        $filename = 'hello_world.pdf';
        $html = '<h1>Hello World</h1>';

        PDF::SetTitle('Hello World');
        PDF::AddPage();
        PDF::writeHTML($html, true, false, true, false, '');

        PDF::Output('hello_world.pdf');
        PDF::Output(public_path($filename), 'F');
        return response()->download(public_path($filename));
    }
}
