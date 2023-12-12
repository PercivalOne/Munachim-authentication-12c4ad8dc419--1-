<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('foo', function () {
    return 'Testing new deployment';
});

$router->group(['prefix' => 'v1/auth'], function () use ($router) {

    $router->group(['middleware' => ['auth']], function () use ($router) {
//        $router->get('validate-token', 'UserController@validateToken');
        $router->get('validate-token', 'UserController@validateNewToken');
        $router->put('update', 'UserController@updateUser');
        $router->put('update/bank', 'UserController@updateBankInformation');
        $router->get('validate-username', 'UserController@convertUsername');
        $router->get('validate-email', 'UserController@validateEmail');
        $router->get('check/username', 'UserController@validateUsername');
        $router->get('campaigns/active-campaigns', 'CampaignController@getActiveCampaign');
        $router->get('payment/banks', 'UserController@bankList');
        $router->get('wallet/{user_id}', 'UserController@fetchWallet');

        $router->get('check/pin', 'UserController@checkPIN');
        $router->post('audience/batch', 'UserController@updateBankInformation');
        $router->get('referrer/count', 'UserController@referrerCount');
        $router->get('referrer/list', 'UserController@referrerList');
    });

    $router->post('credit/wallet', 'AuthController@credithWallet');
    $router->post('audience/get-batch', 'AuthController@getBatch');
    $router->post('user/authentication', 'AuthController@authenticateUser');//check if user credentials exist
    $router->post('send/otp', 'AuthController@processOTP'); //send OTP
    $router->post('verify/otp', 'AuthController@verify'); //verify OTP
    $router->post('create/pin', 'RegisterController@createPIN'); //prompt user to create 4 digit PIN
    $router->post('reset/pin', 'RegisterController@resetPIN'); //prompt user to create 4 digit PIN
    $router->post('complete/pin/reset', 'RegisterController@completePINReset'); //prompt user to create 4 digit PIN
    $router->post('register', 'RegisterController@register'); //user registration
    $router->post('login', 'LoginController@login'); //user login
    $router->post('logout', 'LoginController@logout');//user logout
    $router->get('remove/account', 'RegisterController@removeAccount');//user logout
    $router->get('list/users', 'RegisterController@listUsers');
    $router->get('get/referer/{campaign_id}/{referrer_id}', 'RegisterController@getReferrer');//get referrer

    //Admin Url
    $router->post('admin/login', 'LoginController@adminLogin');
    $router->post('admin/register', 'RegisterController@adminRegister');
    //$router->get('create/user/{user_id}', 'RegisterController@createWallet');
    //$router->post('referrer/create', 'RegisterController@registerReferrer');
});
