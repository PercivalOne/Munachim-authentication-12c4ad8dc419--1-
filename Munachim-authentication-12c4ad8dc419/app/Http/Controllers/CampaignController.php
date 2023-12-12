<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class CampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getActiveCampaign()
    {
        try{
            $response = Http::get(env('CAMPAIGN_SERVICE_URL').'/campaigns/active-campaigns'); //fetch active campaigns
            if ($response->serverError() || $response->clientError()) {
                return response()->json([
                    'message' => $response->json(),
                ], 400);
            }

        }catch (\Exception $exception){
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['status' => true, 'message' => 'Active Campaigns', 'data' => json_decode($response->getBody()->getContents(), true)], 200); //response()->json(['status' => true, 'message' => 'User profile updated', 'data' => $response], 200);
    }
}
