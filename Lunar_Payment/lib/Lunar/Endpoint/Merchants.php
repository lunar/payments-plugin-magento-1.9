<?php

use Lunar\Utils\Cursor;

/**
 * Class Merchants
 *
 * @package Lunar\Endpoint
 */
class Lunar_Endpoint_Merchants extends Lunar_Endpoint_Endpoint
{
    /**
     * @link https://github.com/paylike/api-docs#create-a-merchant
     *
     * @param $args array
     *
     * @return string
     */
    public function create($args)
    {
        $url = 'merchants';

        $api_response = $this->lunar->client->request('POST', $url, $args);

        return $api_response->json['merchant']['id'];
    }

    /**
     * @link https://github.com/paylike/api-docs#fetch-a-merchant
     *
     * @param $merchant_id
     *
     * @return mixed
     */
    public function fetch($merchant_id)
    {
        $url = 'merchants/' . $merchant_id;

        $api_response = $this->lunar->client->request('GET', $url);

        return $api_response->json['merchant'];
    }

    /**
     * https://github.com/paylike/api-docs#update-a-merchant
     * @param $merchant_id
     * @param $args
     *
     * @return void
     */
    public function update($merchant_id, $args)
    {
        $url = 'merchants/' . $merchant_id;

        $this->lunar->client->request('PUT', $url, $args);
    }

    /**
     * @link https://github.com/paylike/api-docs#fetch-all-merchants
     *
     * @param $app_id
     * @param array $args
     * @return Cursor
     * @throws \Exception
     */
    public function find($app_id, $args = array())
    {
        $url = 'identities/' . $app_id . '/merchants';
        if (!isset($args['limit'])) {
            $args['limit'] = 10;
        }
        $api_response = $this->lunar->client->request('GET', $url, $args);
        $merchants = $api_response->json;
        return new Lunar_Utils_Cursor($url, $args, $merchants, $this->lunar);
    }

    /**
     * @link https://github.com/paylike/api-docs#fetch-all-merchants
     *
     * @param $app_id
     * @param $merchant_id
     * @return Lunar_Utils_Cursor
     * @throws Exception
     */
    public function before($app_id, $merchant_id)
    {
        return $this->find($app_id, array('before' => $merchant_id));
    }

    /**
     * @link https://github.com/paylike/api-docs#fetch-all-merchants
     *
     * @param $app_id
     * @param $merchant_id
     * @return Lunar_Utils_Cursor
     * @throws Exception
     */
    public function after($app_id, $merchant_id)
    {
        return $this->find($app_id, array('after' => $merchant_id));
    }
}
