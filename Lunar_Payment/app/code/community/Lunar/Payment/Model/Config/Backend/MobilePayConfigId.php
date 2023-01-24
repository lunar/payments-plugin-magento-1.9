<?php

class Lunar_Payment_Model_Config_Backend_MobilePayConfigId extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $configurationId = $this->getValue();

        if (strlen($configurationId ?? '') != 32) {
            Mage::throwException('The Mobile Pay config id key doesn\'t seem to be valid. It should have exactly 32 characters. Current count: ' . strlen($configurationId));
        }

        return parent::save();
    }
}