<?php

class Lunar_Payment_Model_Source_PaymentAction {

    public function toOptionArray() {
        return array(
            array(
                'value' => Lunar_Payment_Model_Lunar::ACTION_AUTHORIZE,
                'label' => Mage::helper('lunar_payment')->__('Delayed') //Authorize Only
            ),
            array(
                'value' => Lunar_Payment_Model_Lunar::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('lunar_payment')->__('Instant') //Authorize and Capture
            ),
        );
    }

}
