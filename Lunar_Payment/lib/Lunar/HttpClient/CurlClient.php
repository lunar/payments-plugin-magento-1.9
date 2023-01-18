<?php

use Lunar\Exception\ApiConnection;
use Lunar\Exception\ApiException;
use Lunar\Exception\Conflict;
use Lunar\Exception\Forbidden;
use Lunar\Exception\InvalidRequest;
use Lunar\Exception\NotFound;
use Lunar\Exception\Unauthorized;

/**
 * Class CurlClient
 *
 * @package Lunar
 */
class Lunar_HttpClient_CurlClient implements Lunar_HttpClient_HttpClientInterface
{
    const TIMEOUT = 10;

    private $base_url;
    private $api_key;

    public $verify_ssl = true;

    function __construct($api_key, $base_url)
    {
        if ( ! function_exists('curl_init')
            || ! function_exists('curl_setopt')
        ) {
            throw new Lunar_Exception_ApiException("cURL support is required, but can't be found.");
        }

        $this->api_key  = $api_key;
        $this->base_url = $base_url;
    }

    /**
     * Performs the underlying HTTP request. It takes care of handling the
     * connection errors, parsing the headers and the response body.
     *
     * @param  string $http_verb The HTTP verb to use: get, post
     * @param  string $method    The API method to be called
     * @param  array  $args      Assoc array of parameters to be passed
     *
     * @return Lunar_Response_ApiResponse
     * @throws Lunar_Exception_ApiException
     */
    public function request(
        $http_verb,
        $method,
        $args = array()
    ) {

        $timeout = self::TIMEOUT;
        $url     = $this->base_url . '/' . $method;
        $ch      = curl_init();

        // Create a callback to capture HTTP headers for the response
        $response_headers = array();
        $headerCallback   = function ($ch, $header_line) use (&$response_headers
        ) {
            // Ignore the HTTP request line (HTTP/1.1 200 OK)
            if (strpos($header_line, ":") === false) {
                return strlen($header_line);
            }
            list($key, $value) = explode(":", trim($header_line), 2);
            $response_headers[trim($key)] = trim($value);

            return strlen($header_line);
        };
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.api+json',
            'Content-Type: application/vnd.api+json'
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP 1.0.0 (php' . phpversion() .')');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($ch, CURLOPT_USERPWD, ":" . $this->api_key);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, $headerCallback);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $http_verb = strtoupper($http_verb);
        switch ($http_verb) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                $encoded = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
                break;
            case 'GET':
                $query = http_build_query($args, '', '&');
                if ($query) {
                    curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                $encoded = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $encoded = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
                break;
        }

        $response_body = curl_exec($ch);
        if ($response_body === false) {
            $errno   = curl_errno($ch);
            $message = curl_error($ch);
            curl_close($ch);
            $this->handleCurlError($url, $errno, $message);
        }

        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        //
        $json         = $this->parseResponse($response_body, $response_code,
            $response_headers);
        $api_response = new Lunar_Response_ApiResponse($response_body, $response_code,
            $response_headers, $json);

        return $api_response;
    }

    /**
     * @param $response_body
     * @param $response_code
     * @param $response_headers
     *
     * @return mixed
     * @throws Lunar_Exception_ApiException
     */
    private function parseResponse(
        $response_body,
        $response_code,
        $response_headers
    ) {
        $resp = null;
        if ($response_body) {
            $resp      = json_decode($response_body, true);
            $jsonError = json_last_error();
            if ($resp === null && $jsonError !== JSON_ERROR_NONE) {
                $msg = "Invalid response body: $response_body "
                    . "(HTTP response code: $response_code, json_last_error: $jsonError)";
                throw new Lunar_Exception_ApiException($msg, $response_code,
                    $response_body);
            }
        }

        if ($response_code < 200 || $response_code >= 300) {
            $this->handleApiError($response_body, $response_code,
                $response_headers, $resp);
        }

        return $resp;
    }


    /**
     * @param $url
     * @param $errno
     * @param $message
     *
     * @throws Lunar_Exception_ApiConnection
     */
    private function handleCurlError($url, $errno, $message)
    {
        switch ($errno) {
            case CURLE_SSL_CACERT:
            case CURLE_SSL_PEER_CERTIFICATE:
                $msg
                    = "Could not verify Lunar's SSL certificate."; // highly unlikely
                break;
            case CURLE_COULDNT_CONNECT:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_OPERATION_TIMEOUTED:
                $msg
                    = "Could not connect to Lunar ($url).  Please check your internet connection and try again.";
                break;
            default:
                $msg = "Unexpected error communicating with Lunar.";
        }

        $msg .= "\n\n(Network error [errno $errno]: $message)";
        throw new Lunar_Exception_ApiConnection($msg);
    }


    /**
     * @param $response_body
     * @param $response_code
     * @param $response_headers
     * @param $json_resp
     *
     * @throws Lunar_Exception_ApiException
     * @throws Lunar_Exception_Conflict
     * @throws Lunar_Exception_Forbidden
     * @throws Lunar_Exception_InvalidRequest
     * @throws Lunar_Exception_Unauthorized
     */
    private function handleApiError(
        $response_body,
        $response_code,
        $response_headers,
        $json_resp
    ) {

        switch ($response_code) {
            case 400:
                // format for the errors:
                // - [{"field":"amount","message":"Can refund at most GBP 0"}]
                // - [{"code":2,"text":"Invalid card details", "client": true, "merchant": false}]
                $message = "Bad (invalid) request";
                // @TODO - extract error parsing logic
                if ($json_resp && is_array($json_resp) && ! empty($json_resp)) {
                    if (isset($json_resp[0]['message'])) {
                        $message = $json_resp[0]['message'];
                    } else if (isset($json_resp[0]['text'])) {
                        $message = $json_resp[0]['text'];
                    }
                }
                throw new Lunar_Exception_InvalidRequest($message,
                    $response_code, $response_body, $json_resp,
                    $response_headers);
            case 401:
                throw new Lunar_Exception_Unauthorized("You need to provide credentials (an app's API key).",
                    $response_code,
                    $response_body, $json_resp,
                    $response_headers);
            case 403:
                throw new Lunar_Exception_Forbidden("You are correctly authenticated but do not have access.",
                    $response_code, $response_body,
                    $json_resp,
                    $response_headers);
            case 404:
                throw new Lunar_Exception_NotFound("Endpoint not found.",
                    $response_code, $response_body,
                    $json_resp,
                    $response_headers);
            case 409:
                throw new Lunar_Exception_Conflict("Everything you submitted was fine at the time of validation, but something changed in the meantime and came into conflict with this (e.g. double-capture).",
                    $response_code, $response_body,
                    $json_resp,
                    $response_headers);
            default:
                throw new Lunar_Exception_ApiException("Unknown api error",
                    $response_code,
                    $response_body,
                    $json_resp,
                    $response_headers);
        }
    }
}
