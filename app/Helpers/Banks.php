<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class Banks{
    public static function bankList()
    {
        $res = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.env('FLUTTERWAVE_SECRET_KEY_LIVE'),
        ])->get('https://api.flutterwave.com/v3/banks/NG');
        return  json_decode($res->getBody()->getContents(), true);
    }
}
