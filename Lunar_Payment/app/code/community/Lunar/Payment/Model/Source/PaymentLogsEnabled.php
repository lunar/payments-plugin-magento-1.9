<?php

class Lunar_Payment_Model_Source_PaymentLogsEnabled {

    public function toOptionArray() {
        return array(
            array(
                'value' => 1,
                'label' => Mage::helper('lunar_payment')->__('Enabled')
            ),
            array(
                'value' => 0,
                'label' => Mage::helper('lunar_payment')->__('Disabled')
            ),
        );
    }

}
