<?php

class Lunar_Payment_Block_Info_Lunar extends Mage_Payment_Block_Info_Cc {

    protected $_isCheckoutProgressBlockFlag = true;

	/**
	 *
	 */
	protected function _construct() {
        parent::_construct();
    }

}
