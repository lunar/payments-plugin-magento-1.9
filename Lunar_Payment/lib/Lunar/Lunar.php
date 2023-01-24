<?php

/**
 * Class Lunar
 *
 * @package Lunar
 */
class Lunar_Lunar
{
    /**
     * @var string
     */
    const BASE_URL = 'https://api.paylike.io';

    /**
     * @var Lunar_HttpClient_HttpClientInterface
     */
    public $client;

    /**
     * @var string
     */
    private $api_key;


    /**
     * Lunar constructor.
     *
     * @param                          $api_key
     * @param Lunar_HttpClient_HttpClientInterface $client
     * @throws Lunar_Exception_ApiException
     */
    public function __construct($api_key, Lunar_HttpClient_HttpClientInterface $client = null)
    {
        $this->api_key = $api_key;
        $this->client  = $client ? $client
            : new Lunar_HttpClient_CurlClient($this->api_key, self::BASE_URL);
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }


    /**
     * @return Lunar_Endpoint_Apps
     */
    public function apps()
    {
        return new Lunar_Endpoint_Apps($this);
    }

    /**
     * @return Lunar_Endpoint_Merchants
     */
    public function merchants()
    {
        return new Lunar_Endpoint_Merchants($this);
    }

    /**
     * @return Lunar_Endpoint_Transactions
     */
    public function transactions()
    {
        return new Lunar_Endpoint_Transactions($this);
    }

    /**
     * @return Lunar_Endpoint_Cards
     */
    public function cards()
    {
        return new Lunar_Endpoint_Cards($this);
    }
}
