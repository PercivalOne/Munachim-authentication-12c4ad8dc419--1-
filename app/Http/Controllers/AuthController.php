<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{

    public function authenticateUser(Request $request)
    {
        $this->validate($request, [
            'phone_number' => 'required|numeric|digits:11'
        ]);
        try{
            $checkPhoneNumber = User::where('phone_number', $request->phone_number)->first();
            if($checkPhoneNumber == null){
                $message = 'Phone number not registered, OTP send';
                $res = false;
                $phone = '234'.substr($request->phone_number, 1);
                $generate_otp_response = $this->generateOTP($phone);
                if ($generate_otp_response['status'] != "success") {
                    throw new \Exception("something went wrong otp cannot be generated. Try again", 500);
                }
                $otp = $generate_otp_response['data']['otp'];
                $data =  $this->sendOTP($phone, $otp);

            }else{
                $message = 'phone number exist, please enter PIN';
                $res = true;
                $data = null;
            }
        }catch (\Exception $exception) {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['is_registered' => $res, 'message' => $message, 'data' => $data]);

    }

    public function processOTP(Request $request)
    {
        $this->validate($request, [
            'phone_number' => 'required|numeric|digits:11'
        ]);

        try{
            $phone = '234'.substr($request->phone_number, 1);
            $generate_otp_response = $this->generateOTP($phone);
            if ($generate_otp_response['status'] != "success") {
                throw new \Exception("something went wrong otp cannot be generated. Try again", 500);
            }
            $otp = $generate_otp_response['data']['otp'];
            $sentOTP =  $this->sendOTP($phone, $otp);
        }catch (\Exception $exception) {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['status' => true, 'data' => $sentOTP], 200);
    }

    public function generateOTP($phone)
    {
        try {
            $res = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://termii.com/api/sms/otp/generate', [
                "api_key" => env('TERMI_API_KEY'),
                "pin_type" => "NUMERIC",
                "phone_number" => $phone,
                "pin_attempts" => 3,
                "pin_time_to_live" => 60,
                "pin_length" => 4
            ]);
        }catch (\Exception $exception) {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        $response = json_decode($res->getBody()->getContents(), true);
        return $response;

    }

    public function sendOTP($phone, $otp)
    {
        try {
            $res = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://termii.com/api/sms/otp/send', [
                "api_key" => env('TERMI_API_KEY'),
                "message_type" => "NUMERIC",
                "to" => $phone,
                "from" => "BCToken",
                "channel" => "dnd",
                "pin_attempts" => 10,
                "pin_time_to_live" => 60,
                "pin_length" => 6,
                "pin_placeholder" => "< $otp >",
                "message_text" => "Your Arena authorization pin is < $otp >. It expires in 60 minutes",
                "pin_type" => "NUMERIC"
            ]);

        }catch (\Exception $exception) {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        $response = json_decode($res->getBody()->getContents(), true);
        return $response;
        //return response()->json(['status' => true, 'data' => $new_user], 200);
    }

    public function verify(Request $request)
    {
        $this->validate($request, [
            'pin_id' => 'required|string',
            'otp' => 'required|string'
        ]);

        try{
            $verifyOTP = $this->verifyOTP($request->pin_id, $request->otp);
            $data = $verifyOTP['verified'];

            if($data === false)
            {
                $code = '400';
            }elseif($data === 'Expired'){
                $code = '400';
            }else{
                $code = '200';
            }

        }catch (\Exception $e){
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
        return response()->json(['status' => true, 'message'=>'Phone Number Verified', 'data' => $verifyOTP],  $code);
    }

    public function verifyOTP($pin, $otp)
    {
        try{
            $res = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('https://termii.com/api/sms/otp/verify', [
                "api_key" => env('TERMI_API_KEY'),
                "pin_id"=> $pin,
                "pin"=> $otp
            ]);
        }catch (\Exception $exception) {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        $response = json_decode($res->getBody()->getContents(), true);
        return $response;
    }


    public function getBatch(Request $request)
    {
        //dd($request->user_ids);
        $this->validate($request, [
            'user_ids' => 'required'
        ]);

        try{
            $ids = $request->user_ids;
            $fetch = User::whereIn('id', $ids)->get();
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json($fetch, 200);
    }


    public function credithWallet(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|uuid',
            'amount' => 'required|numeric',
        ]);

        try{
            $response = Http::post(env('WALLET_SERVICE_URL').'/wallets/credit', [
                'wallet_id' => $request->user_id,
                'amount' => $request->amount,
                'platform' => 'arena',
                'trans_type' => 'purchase-game-play-credit',
                'reference' => Str::random(17)
            ]);
            if ($response->serverError() || $response->clientError()) {
                return response()->json([
                    'message' => $response->json(),
                ], 400);
            }
            //dd(json_decode($response->getBody()->getContents()));


        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['message' => 'Wallet Credited'], 200);
    }


}
