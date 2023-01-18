<?php

class Lunar_Payment_Model_Config_Backend_PaymentMode extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $paymentMode = $this->getValue();

        Mage::getModel( 'lunar_payment/config_validator_keys')->setMode($paymentMode);

        return parent::save();
    }
}