<?php

/**
 * Class ApiResponse
 *
 * @package Lunar
 */
class Lunar_Response_ApiResponse
{
    public $headers;
    public $body;
    public $json;
    public $code;

    /**
     * ApiResponse constructor.
     *
     * @param $body
     * @param $code
     * @param $headers
     * @param $json
     */
    function __construct(
        $body,
        $code,
        $headers,
        $json
    ) {
        $this->body    = $body;
        $this->code    = $code;
        $this->headers = $headers;
        $this->json    = $json;
    }
}
