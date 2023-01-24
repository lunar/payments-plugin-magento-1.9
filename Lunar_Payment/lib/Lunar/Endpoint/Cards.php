<?php

/**
 * Class Cards
 *
 * @package Lunar\Endpoint
 */
class Lunar_Endpoint_Cards extends Lunar_Endpoint_Endpoint
{
    /**
     * @link https://github.com/paylike/api-docs#save-a-card
     *
     * @param $merchant_id
     * @param $args array
     *
     * @return string
     */
    public function create($merchant_id, $args)
    {
        $url = 'merchants/' . $merchant_id . '/cards';

        $api_response = $this->lunar->client->request('POST', $url, $args);

        return $api_response->json['card']['id'];
    }

    /**
     * @link https://github.com/paylike/api-docs#fetch-a-card
     *
     * @param $card_id
     *
     * @return array
     */
    public function fetch($card_id)
    {
        $url = 'cards/' . $card_id;

        $api_response = $this->lunar->client->request('GET', $url);

        return $api_response->json['card'];
    }
}
