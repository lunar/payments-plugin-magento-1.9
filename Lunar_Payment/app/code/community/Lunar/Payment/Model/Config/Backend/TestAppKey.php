<?php

class Lunar_Payment_Model_Config_Backend_TestAppKey extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $keysValidator = Mage::getModel( 'lunar_payment/config_validator_keys');

        $keysValidator->setAppKey($this->getValue())
            ->validateAppKey()
            ->validateApiKey();

        return parent::save();
    }
}