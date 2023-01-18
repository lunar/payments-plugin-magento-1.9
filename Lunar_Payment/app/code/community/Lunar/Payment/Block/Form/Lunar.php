<?php

class Lunar_Payment_Block_Form_Lunar extends Mage_Payment_Block_Form {

	/**
	 *
	 */
	protected function _construct() {
        parent::_construct();
        $this->setTemplate('lunar/form/lunar.phtml');
    }

}