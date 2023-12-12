<?php

namespace App\Http\Controllers;


use App\Helpers\Banks;
use App\Http\Resources\NewUserResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery\Exception;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

//    public function validateToken(Request $request)  ///token validation
//    {
//        return new UserResource($request->user());
//    }

    public function validateNewToken(Request $request)
    {
        return new NewUserResource($request->user());
    }

    public function updateUser(Request $request)
    {
        $this->validate($request, [
            //'first_name' => 'string',
            //'last_name' => 'string',
            'email' => 'required|email|unique:users',
            'username' => 'string|unique:users'
        ]);

        try{
            $user = User::where('id', auth()->user()->id)->first();

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->username = $request->username;
            $user->save();
        }catch (\Exception $exception){
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['status' => true, 'message' => 'User profile updated', 'data' => $user], 200);
    }

    public function updateBankInformation(Request $request)
    {
        $this->validate($request, [
            'bank_name' => 'required|string',
            'bank_code' => 'required|numeric',
            'account_number' => 'required|numeric|digits:10'
        ]);

        try{
            $bank = User::where('id', auth()->user()->id)->first();
            $bank->bank_name = $request->bank_name;
            $bank->bank_code = $request->bank_code;
            $bank->account_number = $request->account_number;
            $bank->save();
        }catch (\Exception $exception){
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['status' => true, 'message' => 'User bank updated', 'data' => $bank], 200);

    }

    public function validateEmail(Request $request)
    {
        $validated = $this->validate($request, [
            'email' => 'email'
        ]);

        try{
            $getEmail = User::where('email', $validated['email'])->first();
            if($getEmail == null)
            {
                $data = 'Not validated';
                $status = false;
            }else{
                $data = $getEmail;
                $status = true;
            }
        }catch (\Exception $exception){
            return response()->json(['status' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['emailExists' => $status], 200);
    }

    public function validateUsername(Request $request)
    {
        $validated = $this->validate($request, [
            'username' => 'required|string'
        ]);

        try{
            $getUsername = User::where('username', $validated['username'])->first();
            if($getUsername == null)
            {
                $data = 'Not validated';
                $status = false;
            }else{
                $data = $getUsername;
                $status = true;
            }
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['usernameExists' => $status], 200);
    }

    public function convertUsername(Request $request)
    {
        $validated = $this->validate($request, [
            'username' => 'string'
        ]);

        try{
            $getUsername = User::where('username', $validated['username'])->first();
            if($getUsername == null)
            {
                $getUsername = 'Username not valid';
               //$status = false;
            }else{
                $uuid = (string) Str::uuid();
                $getUsername->username = $uuid;
                $getUsername->save();
            }
        }catch (\Exception $exception){
            return response()->json(['status' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['status' => false, 'message' => 'Email Validation',  'data' => $getUsername], 200);
    }

    public function bankList()
    {
        try{
            $banklist = Banks::bankList();
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['data' => $banklist], 200);
    }

    public function fetchWallet($user_id)
    {
        try{
            $response = Http::get(env('WALLET_SERVICE_URL').'/wallets/user/'.$user_id);
            if ($response->serverError() || $response->clientError()) {
                return response()->json([
                    'message' => $response->json(),
                ], 400);
            }
        }catch (\Exception $exception){
            return response()->json(['status' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['status' => false, 'data' => json_decode($response->getBody()->getContents())], 200);
    }




    public function checkPIN(Request $request)
    {
        $this->validate($request, [
            'pin' => 'required|numeric|digits:4'
        ]);

        try{
            $check = User::where('pin', $request->pin)->where('id', auth()->user()->id)->first();
            if($check == null){
                return response()->json(['error' => true, 'is_correct' => false], 500);
               // throw new \Exception("", 500);
            }
        }catch (\Exception $exception){
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error'=> false,'message' => 'fetched', 'is_correct' => true], 200);
    }

    public function referrerCount()
    {
        try{
            $referer_count = User::where('referrer_id',auth()->user()->id)->count();
        }catch (\Exception $exception)
        {
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'referrer_count' => $referer_count], 200);
    }

    public function referrerList()
    {
        try{
            $referrer_list = User::where('referrer_id',auth()->user()->id)->select(['id', 'username', 'first_name', 'last_name', 'email'])->get();
        }catch (\Exception $exception)
        {
            return response()->json(['error' => true, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'referrer_list' => $referrer_list], 200);
    }


}
