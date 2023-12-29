<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\GovEsbHelper\EsbHelper;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;


class NectaResultsQueryController extends Controller
{
    protected $govEsbHelper;
    private $api_key_necta;
    private $api_key_necta_new;
    private $api_tocken_necta;
    private $api_url_necta_auth;
    private $api_url_necta_results;
    private $api_url_nacte_results;
   
    

    public function __construct(EsbHelper $govEsbHelper)
    {
        $this->govEsbHelper = $govEsbHelper;
        // $this->middleware(['role:applicant']);
        $this->api_key_necta = "MjAyMDA0MjAwODM2NDdHRy5FTCRwTUokTHNMRVdUbTV3RGR4RTVqLmx4RXlFVk56WTVKVlRPZFZtRDVEeVQyMkNWaFRVTmV4T2w4VUwyRE5KbmFqbERWQzJWZUVlRG4wT2xlZXlMQ3NMJDhWJHNDLiQ5bFlQVVRHMnhNZDZETFllRHB5eEpwJGxBVU5TdU1BUXkyJG02WTJqaDVDMUM1Q0dEMCQuVVouM04yOWxUM1RTJDE2TXNMTUVsbUp2eHZwLm0=";
        $this->api_key_necta_new = '$2y$10$TECNNrQrQnd.fTUkermXXekUn0O1wqp4jEKjQ760WojuatEW38/Q2';
        $this->api_tocken_necta = "";
        $this->api_url_necta_auth = "https://api.necta.go.tz/api/public/auth"; /*/{key}*/
        //$this->api_url_necta_results = "https://api.necta.go.tz/api/public/results";  /*{index_number}/{exam_id}/{exam_year}/{token}*/
        $this->api_url_necta_results = "https://api.necta.go.tz/api/results/individual";
        $this->api_url_nacte_results = "http://41.93.40.137/nacte_api/index.php/api/results/nvTgJkEx7NEevK/3382b61dd1da84333f21fc93897af48a02e50ae7/18082702020201"; /*{AVN}*/
    }

    public function index()
    {
        // try {
        //     $this->govEsbHelper->getEsbAccessToken();

        //     $accessToken = $this->govEsbHelper->getAccessToken();
            

        //     return response()->json(['access_token' => $accessToken]);
        // } catch (\Exception $e) {
        //     return response()->json(['error' => $e->getMessage()], 500);
        // }

        try {
            $this->govEsbHelper->getEsbAccessToken();
    
            $nectaPayload = [
                "exam_year" => 2010,
                "exam_id" => 1,
                "index_number" => "S1198-0089",
                "api_key" => '$2y$10$V0Q9s.CWtGnRtPQRTVEP3OFv4.UUij4fyQMlRH7ON41Z5GRx5oOnS'
            ];
    
            $accessToken = $this->govEsbHelper->getEsbAccessToken();
            $apiCode = 'Y4NREjvY';
            $response = $this->govEsbHelper->requestData($apiCode, $nectaPayload);
    
            return response()->json(['access_token' => $accessToken, 'response' => $response]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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


    // public function getNectaToken()
    // {
    //     //https://api.necta.go.tz/api/public/auth/MjAyMDA0MjAwODM2NDdHRy5FTCRwTUokTHNMRVdUbTV3RGR4RTVqLmx4RXlFVk56WTVKVlRPZFZtRDVEeVQyMkNWaFRVTmV4T2w4VUwyRE5KbmFqbERWQzJWZUVlRG4wT2xlZXlMQ3NMJDhWJHNDLiQ5bFlQVVRHMnhNZDZETFllRHB5eEpwJGxBVU5TdU1BUXkyJG02WTJqaDVDMUM1Q0dEMCQuVVouM04yOWxUM1RTJDE2TXNMTUVsbUp2eHZwLm0=
    //     //https://api.necta.go.tz/api/public/results/S1504-0050/1/2009/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJpc3N1cmVyIiwiYXVkIjoiMTA4IiwiYWNjIjpbIjEiLCIyIiwiMyJdLCJpYXQiOjE2MTEzMjA0NDUsIm5iZiI6MTYxMTMyMDQ0NSwiZXhwIjoxNjExMzYzNjQ1fQ.P8a0iFaS4ZNJMhBOFLEj_6AiUnvAHREJdUOdB6JHGPg
    //     //https://api.necta.go.tz/api/public/results/S0144-0564/2/2012/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJpc3N1cmVyIiwiYXVkIjoiMTA4IiwiYWNjIjpbIjEiLCIyIiwiMyJdLCJpYXQiOjE2MTEzMjA0NDUsIm5iZiI6MTYxMTMyMDQ0NSwiZXhwIjoxNjExMzYzNjQ1fQ.P8a0iFaS4ZNJMhBOFLEj_6AiUnvAHREJdUOdB6JHGPg
    //     //http://41.93.40.137/nacte_api/index.php/api/results/nvTgJkEx7NEevK/3382b61dd1da84333f21fc93897af48a02e50ae7/18082702020201/17NA00091ME
    //     $url = $this->api_url_necta_auth.'/'.$this->api_key_necta_new;
    //     //$response = Http::get($url, []);
    //     $response = Http::withOptions([
    //                     // 'verify' => false,
    //                     'curl' => [
    //                         //CURLOPT_CAINFO => base_path('resources/assets/cacert.pem'),
    //                         CURLOPT_SSL_VERIFYHOST => 0,  //2
    //                         CURLOPT_SSL_VERIFYPEER => false  //true
    //                     ]
    //                 ])->get($url);
    //     $res = $response->json();
    //     //var_dump($res);
    //     //echo $res['params'][0]['AVN'];
    //     return $res['token']; //(commented on 22/05/2022)
    //     //return $this->api_key_necta_new;
    // }

    // public function get_exam_results_test(Request $request){
    //     $user_id = 1;
    //     $index_no = "S1198-0089";
    //     $year = 2012;
    //     $exam_id = 1; //1 - CSEE
    //     $avn = $request->index_no;
    //     $request->session()->put('year', $year);
    //     $exam_id = 1;
    //     $api_url = $this->api_url_necta_results;
    //     $payload  = [
    //              'exam_year' => $year,
    //              'exam_id"' => 1,
    //              'index_number' => $index_no,
    //              'api_key' => $this->api_key_necta_new
    //             ];
    //     try {
    //         $client = new Client();
    //         $response = $client->request('POST', $api_url, $payload);
    //         $responseData = json_decode($response->getBody()->getContents());
    //         return $responseData;
    //         dd($responseData);
    //     } catch (\GuzzleHttp\Exception\ClientException $e) {
    //         if (request()->ajax()) {
    //             $response = $e->getResponse();
    //             $result = json_decode($response->getBody()->getContents());
    //             return response()->json([
    //                 'errors' => $result,
    //             ]);
    //             dd($response);
    //         } else {
    //             //Log::channel('apiLog')->info('Error' . $e->getMessage() . " line " . $e->getLine() . " in file " . $e->getFile());
    //             //Log::channel('apiLog')->info('::Error');
    //             dd($e->getMessage());
    //         }
    //     }
    // }
}