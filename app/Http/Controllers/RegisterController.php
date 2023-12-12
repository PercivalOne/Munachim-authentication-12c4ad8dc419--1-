<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\UuidTraits;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;


class RegisterController extends Controller
{

    public function createPIN(Request $request)
    {
        $validated =$this->validate($request, [
            'pin' => 'required|numeric|digits:4',
            'phone_number' => 'required|numeric|digits:11|unique:users',
            //'type' => 'sometimes|string',
            //'campaign_id' => 'sometimes|uuid',
            'referrer_code' => 'sometimes'
        ]);

        try {
             $campaign_id = $this->fetchCampaign('Isabisport'); //fetches the campaign _id associated with ISABISPORT(This endpoint basically
            // get the campaign id  by campaign title

            if (!empty($validated['referrer_code'])) {
                //get referrer id
                 $getRefId = User::where('referrer_code', $validated['referrer_code'])->first();
                if($getRefId == null)
                {
                    return response()->json(['error' => true, 'Referrer Code is invalid' => false], 500);
                }

                $referrer = $this->getReferrer($campaign_id, $getRefId->id); //information of the referrer using its referrer ID
                //return $referrer;
                $user = User::create(['phone_number' => $validated['phone_number'], 'pin' => $validated['pin']]); //input user detail;
                $resData = $this->createReferrer($campaign_id, $user->id); //create referrer information for the new user
                $referrer_id = $referrer['data']['referrer_id'];
                $user_referer_code = $resData['data']['code'];
                $user->referrer_code = $user_referer_code;
                $user->referrer_id = $referrer_id;
                $user->save();
                $this->registerReferentActivities($campaign_id, $user->id, $user_referer_code); //register referrer activities

            }else{
                $user = User::create(['phone_number' => $validated['phone_number'], 'pin' => $validated['pin']]);
                $resData = $this->createReferrer($campaign_id, $user->id); //create referrer information for the new user
                $user_referer_code = $resData['data']['code'];
                $user->referrer_code = $user_referer_code;
                $user->save();
                $this->registerReferentActivities($campaign_id, $user->id, $user_referer_code); //register referrer activities

            }

            //process wallet creation
            $loadWallet = ['user_id' => $user->id];
            $wallet = $this->processWalletCreation($loadWallet);

            $campaign = Http::post(env('CAMPAIGN_SERVICE_URL') . '/campaigns/' . $campaign_id . '/subscriptions/freegameplays', [
                'audience_id' => $user->id,
                'campaign_id' => $campaign_id
            ]);
            if ($campaign->serverError() || $campaign->clientError()) {
                return response()->json([
                    'message' => $campaign->json(),
                ], 400);
            }

            $data['user'] = $user;
            $data['wallet'] = $wallet;
        }catch (\Exception $exception){
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['status' => true, 'message' => 'Pin created successfully, you can prompt user to login', 'data' => $data], 200);
    }

    public function fetchCampaign($title)
    {
        try{
            $campaign = Http::get(env('CAMPAIGN_SERVICE_URL') . '/campaigns/fetch-campaigns/'.$title );
            if ($campaign->serverError() || $campaign->clientError()) {
                return response()->json([
                    'message' => $campaign->json(),
                ], 400);
            }
        }catch (\Exception $exception)
        {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }

        return json_decode($campaign->getBody()->getContents(), true)['data'];
    }

    public function createReferrer($campaign_id, $referrer_id)
    {
        try{
            $campaignReferrer = Http::post(env('CAMPAIGN_SERVICE_URL') . '/campaigns/' . $campaign_id . '/referrals', [
                'referrer_id' => $referrer_id
            ]);
            if ($campaignReferrer->serverError() || $campaignReferrer->clientError()) {
                return response()->json([
                    'message' => $campaignReferrer->json(),
                ], 400);
            }
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return json_decode($campaignReferrer->getBody()->getContents(), true);
    }

    public function registerReferentActivities($campaign_id, $user_id, $referrer_code)
    {
        try{
            $registerReferentActivities = Http::post(env('CAMPAIGN_SERVICE_URL') . '/campaigns/' . $campaign_id . '/referrals/activities', [
                'referent_id' => $user_id,
                'referral_code' => $referrer_code//$validated['referrer_code']
            ]);
            if ($registerReferentActivities->serverError() || $registerReferentActivities->clientError()) {
                return response()->json([
                    'message' => $registerReferentActivities->json(),
                ], 400);
            }
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return json_decode($registerReferentActivities->getBody()->getContents(), true);
    }

    public function processWalletCreation($loadWallet)
    {
        try{
            $processWalletCreation = Http::post(env('WALLET_SERVICE_URL').'/wallets/create', $loadWallet);
            if($processWalletCreation->serverError() || $processWalletCreation->clientError()){
                return response()->json([
                    'message' => $processWalletCreation->json()
                ], 400);
            }
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return json_decode($processWalletCreation->getBody()->getContents(), true);
    }

    //get referer | OUTSIDE FUNCTION
    public function getReferrer($campaign_id, $referrer_id)
    {
        try{
            $campaignReferrer = Http::get(env('CAMPAIGN_SERVICE_URL') . '/campaigns/' . $campaign_id . '/referrals/'.$referrer_id.'/referrer');
            if ($campaignReferrer->serverError() || $campaignReferrer->clientError()) {
                return response()->json([
                    'message' => $campaignReferrer->json(),
                ], 400);
            }
            //$resData = json_decode($campaignReferrer->getBody()->getContents(), true);
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return json_decode($campaignReferrer->getBody()->getContents(), true);//response()->json($resData,200);
    }


    public function adminRegister(Request $request)
    {
        $validated =$this->validate($request, [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
            'phone_number' => 'required|numeric|digits:11|unique:users'
        ]);

        try{
            $create = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone_number' => $validated['phone_number'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'pin' => '1111',
                'role' => 'admin'
            ]);
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        $data['user'] =  $create;
        $data['token'] =  $create->createToken('Arena')->accessToken;
        return response()->json(['error' => false, 'message' => 'admin registration successful', 'data' => $data], 200);
    }














    public function resetPIN(Request $request)
    {
        $validated = $this->validate($request, [
            'phone_number' => 'required|numeric|digits:11',
            //'pin' => 'required|numeric|digits:4'
        ]);

        try{
                $reset = User::where('phone_number', $request->phone_number)->first();
                if ($reset == null) {
                    return response()->json(['error' => true, 'message' => 'Phone Number does not exist'], 500);
                }
                    $phone = '234' . substr($validated['phone_number'], 1);
                    $generate_otp_response = $this->generateNewOTP($phone);
                    if ($generate_otp_response['status'] != "success") {
                        response()->json(['error' => true, 'message' => 'something went wrong otp cannot be generated. Try again'], 500);
                        //throw new \Exception("something went wrong otp cannot be generated. Try again", 500);
                    }
                    $otp = $generate_otp_response['data']['otp'];
                    $data = $this->sendNewOTP($phone, $otp);

            }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'message' => 'prompt user to enter new pin', 'data' => $data], 200);
    }


    public function generateNewOTP($phone)
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

    public function sendNewOTP($phone, $otp)
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
                "message_text" => "Your TopBrain reset pin is < $otp >. It expires in 60 minutes",
                "pin_type" => "NUMERIC"
            ]);

        }catch (\Exception $exception) {
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        $response = json_decode($res->getBody()->getContents(), true);
        return $response;
        //return response()->json(['status' => true, 'data' => $new_user], 200);
    }

    public function completePINReset(Request $request)
    {
        $this->validate($request, [
            'phone_number' => 'required|numeric|digits:11',
            'new_pin' => 'required|numeric|digits:4'
        ]);

        try{
            $reset = User::where('phone_number', $request->phone_number)->first();
            $reset->pin = $request->new_pin;
            $reset->save();
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'message' =>'PIN Reset completed', 'data' => $reset], 200);
    }

    public function removeAccount(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|uuid'
        ]);

        try{
            $remove_account = User::where('id', $request->user_id)->first();
            if($remove_account == null)
            {
                throw new \Exception("User could not be deleted", 500);
            }
            $remove_account->delete();
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'message' =>'Account Deleted'], 200);
    }

    public function listUsers()
    {
        try{
            $user = User::all();

        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'data' => $user], 200);
    }

    public function registerReferrer(Request $request)
    {
        $validated = $this->validate($request, [
            'pin' => 'required|numeric|digits:4',
            'phone_number' => 'required|numeric|digits:11|unique:users',
            'type' => 'sometimes|string',
            'campaign_id' => 'sometimes|uuid',
            'referrer_username' => 'required|string'
        ]);

        try{
            $checkUsername = User::where('username', $validated['referrer_username'])->first();
            if($checkUsername == null){
                throw new \Exception("referrer username not valid", 500);
                //response()->json(['error' => true, 'message' => 'referrer username not valid'], 500);
            }
            $user = User::create(['phone_number' => $validated['phone_number'], 'pin' => $validated['pin'], 'referrer_id' => $checkUsername->id]);
            if($user) {
                if ($validated['type'] == 'isabisport') {
                    //create free gameplay subscriptions
                    $campaign_id = $validated['campaign_id'];//"bf2d9d36-e9e9-4999-9213-60913e6d871f";
                    $campaign = Http::post(env('CAMPAIGN_SERVICE_URL') . '/campaigns/' . $campaign_id . '/subscriptions/freegameplays', [
                        'audience_id' => $user->id,
                        'campaign_id' => $campaign_id
                    ]);
                    if ($campaign->serverError() || $campaign->clientError()) {
                        return response()->json([
                            'message' => $campaign->json(),
                        ], 400);
                    }
                }

                $loadWallet = ['user_id' => $user->id];
                $processWalletCreation = Http::post(env('WALLET_SERVICE_URL') . '/wallets/create', $loadWallet);
                if ($processWalletCreation->serverError() || $processWalletCreation->clientError()) {
                    return response()->json([
                        'message' => $processWalletCreation->json()
                    ], 400);
                }
            }

            $data['user'] = $user;
            $data['wallet'] = json_decode($processWalletCreation->getBody()->getContents(), true);
            $data['referrer'] = $checkUsername;

        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => true, 'message' =>'Pin Created Successfully, prompt user to login', 'data' => $data], 500);
    }
}
