<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\GovEsbHelper\EsbHelper;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;


class NidaVerificationController extends Controller
{
    protected $govEsbHelper;
    // private $api_key_necta;
    // private $api_key_necta_new;
    // private $api_tocken_necta;
    // private $api_url_necta_auth;
    // private $api_url_necta_results;
    // private $api_url_nacte_results;
   
    

    public function __construct(EsbHelper $govEsbHelper)
    {
        $this->govEsbHelper = $govEsbHelper;

    }


    public function nida()
    {
        try {
            // Get the access token
            $accessToken = $this->govEsbHelper->getEsbAccessToken();
    
            // Prepare the NIDA payload
            $nidaPayload = [
                "NIN" => "20020427251130000124"
            ];
            $apiCode = '0KG8y54T';

            // Sign the payload
            $response = $this->govEsbHelper->requestNida($apiCode, $nidaPayload);
            if (!$response) {
                //Signature verification has failed.
                //Throw exception ama handle kama ikifeli signature verification
            }
    
            $esbRequestId = $response["requestId"];
            $esbSuccess = $response["success"];
            $producerResponse = $response["esbBody"];
    
            if (!$esbSuccess) {
                //handle failure
            }
    
            // Set up the API code
    
            // Make the request
            // $response = $this->govEsbHelper->requestData($apiCode, json_decode($signedPayload, true));
    
            return response()->json(['access_token' => $accessToken, 'response' => $response]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}