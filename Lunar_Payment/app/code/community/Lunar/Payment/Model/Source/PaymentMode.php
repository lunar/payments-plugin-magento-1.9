<?php

class Lunar_Payment_Model_Source_PaymentMode {
    const LIVE_MODE_VALUE = 'live';
    const TEST_MODE_VALUE = 'test';

    public function toOptionArray() {
        return array(
            array(
                'value' => self::TEST_MODE_VALUE,
                'label' => Mage::helper('lunar_payment')->__('Test')
            ),
            array(
                'value' => self::LIVE_MODE_VALUE,
                'label' => Mage::helper('lunar_payment')->__('Live')
            ),
        );
    }

}
