<?php

namespace App\Http\Controllers;

class PaymentController extends APIController
{
    public function pay()
    {
        $curl = curl_init();

        $bond = 10;
        $bondamount = $bond * 100;
        $referenceid = '11783_brimbankcitycouncil122' . rand(0, 1000);
        $cardtoken = 'cb64fet7gzy9ty8i71j9';

        $username = env('FATZEBRA_USERNAME');
        $password = env('FATZEBRA_ACCESS_TOKEN');

        $fields = [
            'amount' => $bondamount,
            'reference' => $referenceid,
            'customer_ip' => \request()->ip(),
            'currency' => 'AUD',
            'card_number' => '5123 4567 8901 2346',
            'card_holder' => 'Mr Bob',
            'card_expiry' => '12/2222',
            // 'card_token' => $cardtoken,
            'capture' => false,
            'final' => false,
            'extra' => ['ecm' => '31', 'card_on_file' => false],
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://gateway.pmnts-sandbox.io/v1.0/purchases",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($fields),
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
            CURLOPT_USERPWD => $username . ':' . $password,
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "content-type: application/json",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $response = "cURL Error #:" . $err;
        } else {
            $response = json_decode($response);
        }
        return $this->successResponse(['data' => $response]);
    }
    // authorise
    public function authorise()
    {

    }
    // release
    public function release()
    {

    }
    // refund
    public function refund()
    {

    }
}
