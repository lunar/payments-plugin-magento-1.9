<?php

/**
 * Class Endpoint
 *
 * @package Lunar\Endpoint
 */
abstract class Lunar_Endpoint_Endpoint
{
    /**
     * @var Lunar_Lunar
     */
    protected $lunar;

    /**
     * Endpoint constructor.
     *
     * @param $lunar
     */
    function __construct($lunar)
    {
        $this->lunar = $lunar;
    }
}
