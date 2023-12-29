<?php

namespace App\GovEsbHelper;

use DOMDocument;
use DOMException;
use Exception;
use SimpleXMLElement;
use Throwable;

class EsbHelper
{
    public function __construct($apiCode = null, $requestBody = null, $format = null)
    {
        $this->clientId = env("GOVESB_CLIENT_ID");
        $this->clientSecret = env("GOVESB_CLIENT_SECRET");
        $this->clientPrivateKey = env("GOVESB_PRIVATE_KEY");
        $this->esbPublicKey = env("GOVESB_ESB_PUBLIC_KEY");
        $this->esbTokenUrl = env("GOVESB_ESB_TOKEN_URL");
        $this->esbEngineUrl = env("GOVESB_ESB_ENGINE_URL");
        $this->nidaUserId = env("GOVESB_NIDA_USER_ID");
        $this->signatureAlogirthm = "ECC";
        $this->format = "json";
        $this->apiCode = $apiCode;
        $this->requestBody = $requestBody;
    }

    public $clientPrivateKey;
    public $esbPublicKey;
    public $clientId;
    public $clientSecret;
    public $esbTokenUrl;
    public $esbEngineUrl;
    public $nidaUserId;
    public $apiCode;
    public $requestBody;
    public $format;
    public $accessToken;
    private $signatureAlogirthm;

    public function __toString()
    {
        $object = [
            "apiCode" => $this->apiCode,
            "esbBody" => $this->requestBody,
            "format" => $this->format,
            "access_token" => $this->accessToken,
            "clientId" => $this->clientId,
            "clientSecret" => $this->clientSecret,
            "clientPrivateKey" => $this->clientPrivateKey,
            "esbPublicKey" => $this->esbPublicKey,
            "esbTokenUrl" => $this->esbTokenUrl,
            "esbEngineUrl" => $this->esbEngineUrl,
            "nidaUserId" => "EGA",
        ];

        return $this->jsonEncode($object);
    }

    /**
     * @throws EsbHelperException
     * @throws Exception
     */
    // public function getEsbAccessToken()
    // {
    //     if (!$this->clientId || !$this->clientSecret) {
    //         throw new EsbHelperException("clientId or clientSecret is null");
    //     }

    //     $curl = curl_init();
    //     $usernamePassword = $this->clientId.":".$this->clientSecret;
    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL => $this->esbTokenUrl,
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => '',
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 30,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => 'POST',
    //         CURLOPT_POSTFIELDS => array(
    //             'grant_type' => 'client_credentials',
    //             'client_id' => $this->clientId,
    //             'client_secret' => $this->clientSecret,
    //         ),
    //         CURLOPT_HTTPHEADER => array(
    //             'Authorization: Basic ' . base64_encode($usernamePassword),
    //         ),
    //     ));

    //     $response = curl_exec($curl);
    //     $error = curl_error($curl);  // Capture cURL error
    //     curl_close($curl);

    //     if ($error) {
    //         throw new Exception("Curl error: $error");
    //     }

    //     // Check if $esbTokenResponse is not null before decoding
    //     $esbTokenResponse = $response ? json_decode($response, true) : null;
    //     if (!$esbTokenResponse || !array_key_exists("access_token", $esbTokenResponse)) {
    //         throw new EsbHelperException("Could not get token from GovESB. Response: " . $response);
    //     }

    //     $this->accessToken = $esbTokenResponse["access_token"];
    //     return $this;
    // }
    public function getEsbAccessToken()
    {
        if (!$this->clientId || !$this->clientSecret) {
            throw new EsbHelperException("clientId or clientSecret is null");
        }
        $curl = curl_init();
        $usernamePassword = $this->clientId . ":" . $this->clientSecret;
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->esbTokenUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => '10',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . base64_encode($usernamePassword)
            ),
        ));

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $error = 'Curl error: ' . curl_error($curl);
        }
        curl_close($curl);

        if (isset($error)) {
            throw new Exception($error);
        }
        $esbTokenResponse = json_decode($response, true);

        if (!array_key_exists("access_token", $esbTokenResponse)) {
            throw new EsbHelperException("Could not get token from GovESB");
        }

        $this->accessToken = $esbTokenResponse["access_token"];

        return $this;
    }

    public function requestData($apiCode = null, $esbBody = null, $format = null, $headers = null)
    {
        return $this->request($this->esbEngineUrl . "/request", false, false, $apiCode, $esbBody, $format, $headers);
    }

    public function requestNida($apiCode = null, $esbBody = null, $format = null)
    {
        return $this->request($this->esbEngineUrl . "/nida-request", true, false, $apiCode, $esbBody, $format);
    }


    public function pushData($apiCode = null, $payload = null, $format = null)
    {
        return $this->request($this->esbEngineUrl . "/push-request", false, true, $apiCode, $payload, $format);
    }

    /**
     * @throws EsbHelperException
     */
    private function request($esbUrl, $isNidaRequest, $isPushRequest, $apiCode = null, $esbBody = null, $format = null, $headers = null)
    {
        if (!$this->accessToken) {
            $this->getEsbAccessToken();
        }
        $this->validateRequestParameters($apiCode, $format, $esbBody);
        $esbRequestBody = $this->createEsbRequest($isPushRequest, $isNidaRequest ? $this->nidaUserId : null);


        $response = $this->esbCurlRequest($esbRequestBody, $esbUrl, $headers);

        
        return $this->verifyAndGetData($response);
    }

    private function esbCurlRequest($requestBody, $esbRequestUrl, $headers = null)
    {
        $esbHeaders = ['Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/' . $this->format];


        if ($headers && is_array($headers)) {
            array_merge($esbHeaders, $headers);
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $esbRequestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $requestBody,
            CURLOPT_HTTPHEADER => $esbHeaders,
        ));
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $error = 'Curl error: ' . curl_error($curl);
        }
        curl_close($curl);

        if (isset($error)) {
            return $error;
        }

        return $response;
    }

    /**
     * 2@throws EsbHelperException
     */
    private function createEsbRequest($isPushRequest = false, $userId = null)
    {
        if ($this->format == DataFormat::json) {
            return $this->createJsonRequest($isPushRequest, $userId);
        } else if ($this->format == DataFormat::xml) {
            return $this->createXmlRequest($isPushRequest, $userId);
        }
        return null;
    }

    /**
     * @throws EsbHelperException
     */
    private function response(bool $success, $data, $format = null)
    {
        if ($format) {
            $this->format = $format;
        }
        if (!$this->format) {
            throw new EsbHelperException("Data format not set");
        }
        if ($this->format == DataFormat::json) {
            return $this->createJsonResponse($success, $data, false);
        } else {
            return $this->createXmlResponse($success, $data, false);
        }
    }

    /**
     * @throws EsbHelperException
     */
    public function successResponse($data, $format = null)
    {
        return $this->response(true, $data, $format);
    }

    /**
     * @throws EsbHelperException
     */
    public function failureResponse($message, $format = null)
    {
        return $this->response(false, $message, $format);
    }

    /**
     * @throws EsbHelperException
     * @throws DOMException
     */
    private function createXmlRequest($isPushRequest, $userId)
    {
        $doc = new DOMDocument();
        $esbRequestRoot = $doc->createElement('esbrequest');

        $data = $doc->createElement("data");
        if ($isPushRequest) {
            $data->appendChild($doc->createElement("pushCode", $this->apiCode));
        } else {
            $data->appendChild($doc->createElement("apiCode", $this->apiCode));
        }
        if ($userId) {
            $data->appendChild($doc->createElement("userId", $userId));
        }

        if ($this->requestBody) {
            if (!$this->_isValidXML($this->requestBody)) {
                throw new EsbHelperException("Invalid xml");
            }
            $this->requestBody = $this->format_xml($this->requestBody);

            if ($userId) {
                $this->requestBody = "<Payload>" . $this->format_xml($this->requestBody) . "</Payload>";
            }

            $fragment = $doc->createDocumentFragment();
            $fragment->appendXml("<esbBody>" . $this->requestBody . "</esbBody>");
            $data->appendChild($fragment);
        }
        $esbRequestRoot->appendChild($data);
        $dataString = $data->ownerDocument->saveXML($data, LIBXML_NOEMPTYTAG);
        $signature = $this->signPayload($dataString);
        $esbRequestRoot->appendChild($doc->createElement("signature", $signature));
        $doc->appendChild($esbRequestRoot);
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        return $doc->saveXML($doc->documentElement, LIBXML_NOEMPTYTAG);
    }

    /**userId
     * @throws EsbHelperException
     */
    private function createJsonRequest($isPushRequest, $userId)
    {
        $data = [];
        if ($this->requestBody) {
            if (is_string($this->requestBody)) {
                $this->requestBody = $this->jsonDecode($this->requestBody);
            }

            if (!is_array($this->requestBody)) {
                throw new EsbHelperException("Invalid esbBody");
            }
        }

        if ($isPushRequest) {
            $data["pushCode"] = $this->apiCode;
        } else {
            $data["apiCode"] = $this->apiCode;
        }
        if ($userId) {
            $data["userId"] = $userId;

            if (key_exists("Payload", $this->requestBody)) {
                $data["esbBody"] = [
                    $this->requestBody
                ];
            } else {
                $data["esbBody"] = [
                    "Payload" => $this->requestBody
                ];
            }
        } else {
            $data["esbBody"] = $this->requestBody;
        }

        return $this->signJsonData($data);
    }

    private function createJsonResponse($isSuccess, $array, $isAsyncResponse)
    {
        $data["success"] = $isSuccess;
        if (!$isSuccess) {
            if (is_array($array)) {
                $data["esbBody"] = $array;
            }
            if (is_string($array)) {
                $data["message"] = $array;
            }
        } else {
            $data["esbBody"] = $array;
        }
        if ($isAsyncResponse) {
            $data["requestId"] = $this->apiCode;
        }
        return $this->signJsonData($data);
    }

    private function signJsonData($array)
    {
        $data = $this->jsonEncode($array);
        $signature = $this->signPayload($data);
        $esbRequest = [
            "data" => json_decode($data),
            "signature" => $signature
        ];
        return $this->jsonEncode($esbRequest);
    }

    /**
     * @throws EsbHelperException
     * @throws DOMException
     */
    private function createXmlResponse($isSuccess, $xmlString, $isAsyncResponse)
    {
        if (!is_string($xmlString)) {
            throw new EsbHelperException("Invalid response body, expected xml string or message");
        }
        if ($isSuccess && !$this->_isValidXML($xmlString)) {
            throw new EsbHelperException("Invalid xml");
        }
        $doc = new DOMDocument();
        $esbRequestRoot = $doc->createElement('esbresponse');

        $data = $doc->createElement("data");
        $data->appendChild($doc->createElement("success", $isSuccess ? "true" : "false"));
        if ($isAsyncResponse) {
            $data->appendChild($doc->createElement("requestId", $this->apiCode));
        }
        if (!$isSuccess && !$this->_isValidXML($xmlString)) {
            $data->appendChild($doc->createElement("message", $xmlString));
        } else {
            $xml = $this->format_xml($xmlString);
            $fragment = $doc->createDocumentFragment();
            $fragment->appendXml("<esbBody>" . $xml . "</esbBody>");
            $data->appendChild($fragment);
        }
        $dataString = $data->ownerDocument->saveXML($data, LIBXML_NOEMPTYTAG);
        $signature = $this->signPayload($dataString);
        $esbRequestRoot->appendChild($data);
        $esbRequestRoot->appendChild($doc->createElement("signature", $signature));
        $doc->appendChild($esbRequestRoot);
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        return $doc->saveXML($doc->documentElement, LIBXML_NOEMPTYTAG);
    }

    public function verifyAndGetData($esbRequestBody)
    {
        if ($this->format == DataFormat::json) {
            $jsonPayload = EsbHelper::jsonDecode($esbRequestBody);
            $signature = $jsonPayload["signature"];

            $signedData = $this->getSignedPayload($esbRequestBody);
            $data = EsbHelper::jsonDecode($signedData);
        } else {
            $doc = new SimpleXMLElement($esbRequestBody);
            $signature = $doc->signature;
            $signedData = $this->format_xml($doc->data->saveXML());
            $data = $signedData;
        }

        $valid = $this->verifyPayload($signedData, $signature);
        if ($valid != 1) {
            return null;
        }

        return $data;
    }

    private function getSignedPayload($esbPayload)
    {
        $dataStartIndex = strpos($esbPayload, "{", strpos($esbPayload, "data"));
        $dataEndIndex = strripos($esbPayload, ',"signature"');
        return substr($esbPayload, $dataStartIndex, $dataEndIndex - 8);
    }

    /**
     * @throws EsbHelperException|DOMException
     */
    private function asyncResponse(bool $success, $data, $requestId = null, $format = null)
    {
        if (!$this->accessToken) {
            $this->getEsbAccessToken();
        }
        $this->validateRequestParameters($requestId, $format, $data);

        if ($this->format == DataFormat::json) {
            $response = $this->createJsonResponse($success, $data, true);
        } else {
            $response = $this->createXmlResponse($success, $data, true);
        }

        return self::esbCurlRequest($response, $this->esbEngineUrl . "/async");
    }

    /**
     * @throws DOMException
     * @throws EsbHelperException
     */
    public function asyncSuccessResponse($data, $requestId = null, $format = null)
    {
        return $this->asyncResponse(true, $data, $requestId, $format);
    }

    /**
     * @throws DOMException
     * @throws EsbHelperException
     */
    public function asyncFailureResponse($data, $requestId = null, $format = null)
    {
        return $this->asyncResponse(false, $data, $requestId, $format);
    }


    // UTIL FUNCTIONS
    public function signPayload($payload, $privateKey = null)
    {
        if ($privateKey) {
            $this->clientPrivateKey = $privateKey;
        }
        $signature = "";
        if ($this->signatureAlogirthm == "ECC") {
            $privateKey = $this->getPrivateKey($this->clientPrivateKey);
        } else {
            $privateKey = $this->getRsaPrivateKey($this->clientPrivateKey);
        }
        openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    public function verifyPayload($payload, $signature, $publicKey = null)
    {
        if ($publicKey) {
            $this->esbPublicKey = $publicKey;
        }
        $publicKey = $this->getPublicKey($this->esbPublicKey);
        $signature = base64_decode($signature);
        return openssl_verify($payload, $signature, $publicKey, OPENSSL_ALGO_SHA256);
    }

    private function getPrivateKey($privKey)
    {
        $privKey = "-----BEGIN EC PRIVATE KEY-----\n" . $privKey . "\n-----END EC PRIVATE KEY-----\n";
        return openssl_get_privatekey($privKey);
    }

    private function getRsaPrivateKey($privKey)
    {
        $privKey = "-----BEGIN RSA PRIVATE KEY-----\n" . $privKey . "\n-----END RSA PRIVATE KEY-----\n";
        return openssl_get_privatekey($privKey);
    }

    private function getPublicKey($pubKey)
    {
        $pubKey = "-----BEGIN PUBLIC KEY-----\n" . $pubKey . "\n-----END PUBLIC KEY-----\n";
        return openssl_get_publickey($pubKey);
    }

    private function jsonEncode(array $data)
    {
        return json_encode($data, JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function jsonDecode(string $data)
    {
        return json_decode($data, true, 512, JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @throws EsbHelperException
     */
    private function validateRequestParameters($apiCode, $format, $esbBody)
    {
        if ($apiCode) {
            $this->apiCode = $apiCode;
        }

        if ($esbBody) {
            $this->requestBody = $esbBody;
        }

        if ($format) {
            $this->format = $format;
        }

        if (!$this->apiCode) {
            throw new EsbHelperException("apiCode can not be null");
        }

        if (!$this->format) {
            throw new EsbHelperException("format can not be null");
        }
    }

    private function _isValidXML($xml)
    {
        $doc = @simplexml_load_string($xml);
        if ($doc) {
            return true; //this is valid
        } else {
            return false; //this is not valid
        }
    }

    function format_xml($xml)
    {
        $sxe = simplexml_load_string($xml);
        $domElement = dom_import_simplexml($sxe);
        $domDocument = $domElement->ownerDocument;
        $domDocument->preserveWhiteSpace = false;
        $domDocument->formatOutput = false;
        $domDocument->loadXML($sxe->asXML(), LIBXML_NOBLANKS); // Fixes newlines omitted by DomNode::appendChild()
        return $domDocument->saveXML($domDocument->documentElement, LIBXML_NOEMPTYTAG);
    }

    /**
     * @param mixed $accessToken
     */
    public function setAccessToken($accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @param mixed|null $format
     */
    public function setFormat($format): void
    {
        $this->format = $format;
    }

    /**
     * @param mixed|null $requestBody
     */
    public function setRequestBody($requestBody): void
    {
        $this->requestBody = $requestBody;
    }

    private function readKeyFile($path)
    {
        return str_replace(array("\n", "\r"), '', file_get_contents(storage_path($path)));
    }
}

abstract class DataFormat
{
    const xml = "xml";
    const json = "json";
}

class EsbHelperException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
