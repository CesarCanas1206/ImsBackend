<?php

namespace App\Http\Controllers;

class AiController extends Controller
{
    public function run()
    {
        if (empty(request()->get('prompt'))) {
            return response()->json(['data' => 'No API prompt set']);
        }

        $data = [
            'model' => 'text-davinci-003',
            'prompt' => request()->prompt,
            'max_tokens' => 500,
            'temperature' => 0.5,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = [];
        $headers[] = "Content-Type: application/json";
        $headers[] = "Authorization: Bearer " . env('OPENAI_API_KEY');

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $result = json_decode($result);

        return response()->json(['data' => $result]);
    }
}
