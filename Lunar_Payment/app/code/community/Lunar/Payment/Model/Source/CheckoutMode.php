<?php

class Lunar_Payment_Model_Source_CheckoutMode
{
    const AFTER_ORDER = 'after_order';
    const BEFORE_ORDER = 'before_order';

    public function toOptionArray() {
        return array(
            array(
                'value' => self::AFTER_ORDER,
                'label' => Mage::helper('lunar_payment')->__('Redirect to payment page after order created')
            ),
            array(
                'value' => self::BEFORE_ORDER,
                'label' => Mage::helper('lunar_payment')->__('Payment before order created')
            ),
        );
    }

}
