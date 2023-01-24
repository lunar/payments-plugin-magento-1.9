<?php

class Lunar_Payment_Block_Form_Lunarmobilepay extends Mage_Payment_Block_Form {

	/**
	 *
	 */
	protected function _construct() {
        parent::_construct();
        $this->setTemplate('lunar/form/lunarmobilepay.phtml');
    }

}