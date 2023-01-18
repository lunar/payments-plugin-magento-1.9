<?php

class Lunar_Payment_Model_Config_Backend_CheckoutMode extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $checkoutMode = $this->getValue();

        Mage::getModel( 'lunar_payment/config_validator_keys')->setMode($checkoutMode);

        return parent::save();
    }
}