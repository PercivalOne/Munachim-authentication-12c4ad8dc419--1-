<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class LoginController extends Controller
{

    public function login(Request $request)
    {
        $this->validate($request, [
            'phone_number' => 'required|numeric|digits:11',
            'pin' => 'required|numeric|digits:4',
        ]);

        try{
            $verifyPin = User::where('pin', $request->pin)->where('phone_number', $request->phone_number)->first();//firstOrFail();
            if($verifyPin == null)
            {
                throw new \Exception("Wrong pin. Try again or click on forgot pin", 401);
            }
        }catch(\Exception $exception){
            return response()->json(['message' => $exception->getMessage()], 500);
        }
        $user['user'] = $verifyPin;
        $user['token'] = $verifyPin->createToken('Brandmobile')->accessToken;
        return response()->json(['is_correct' => true, 'message' => 'Login Successful', 'data' => $user], 200);
    }

    public function adminLogin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        try{
            $user = User::where('email', $request->email)->where('role', 'admin')->first();
            if(is_null($user)){
                return response()->json(['error' => true, 'message' => 'Credentials not correct'], 400);
            }
            if(!Hash::check($request->password, $user->password)){
                return response()->json(['error' => true, 'message' => 'Credentials not correct'], 400);
            }
        }catch(\Exception $exception){
            return response()->json(['message' => $exception->getMessage()], 500);
        }
        $data['user'] =  $user;
        $data['token'] =  $user->createToken('Brandmobile')->accessToken;
        return response()->json(['error' => false, 'message' => 'admin login successful', 'data' => $data], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ], 201);
    }

}
