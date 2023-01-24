<?php

class Lunar_Payment_Model_Lunarlogos extends Mage_Core_Model_Abstract {

    const PAYMENT_LOGO_PATH = '/frontend/lunar/logos/';

	/**
	 *
	 */
	protected function _construct() {
        $this->_init('lunar_payment/lunarlogos');
    }

}
