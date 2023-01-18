<?php


class Lunar_Payment_Model_Config_Validator_Keys
{
    protected static $appKey;

    protected static $apiKey;

    protected static $livePublicKeys = [];

    protected static $testPublicKeys = [];

    protected static $mode;

    /**
     * Method used for checking if the new value is valid before saving.
     *
     * @return $this
     */
    public function validateAppKey()
    {
        /** Check if the new value is empty. */
        if (!self::$appKey) {
            return $this;
        }

        /** Create  Api client. */
        $lunarClient = new Lunar_Lunar(self::$appKey);

        /** Validate the live app key by extracting the identity of the lunar client. */
        try {
            $identity = $lunarClient->apps()->fetch();
        } catch(Lunar_Exception_ApiException $exception ) {
            /** Mark the new value as invalid */
            $message = self::$mode === Lunar_Payment_Model_Source_PaymentMode::TEST_MODE_VALUE ? Mage::helper( 'lunar_payment' )->__("The test private key doesn't seem to be valid.") : Mage::helper( 'lunar_payment' )->__("The live private key doesn't seem to be valid.");

            Mage::throwException($message);
        }

        /** Extract and save all the live public keys of the merchants with the above extracted identity. */
        try {
            if (self::$mode === Lunar_Payment_Model_Source_PaymentMode::TEST_MODE_VALUE) {
                $this->fetchTestKeys($lunarClient, $identity['id']);
            } else if (self::$mode === Lunar_Payment_Model_Source_PaymentMode::LIVE_MODE_VALUE) {
                $this->fetchLiveKeys($lunarClient, $identity['id']);
            }
        } catch (Lunar_Exception_ApiException $exception ) {
            // we handle in the following statement
            $message = self::$mode === Lunar_Payment_Model_Source_PaymentMode::TEST_MODE_VALUE ? Mage::helper( 'lunar_payment' )->__("The test private key is not valid or set to test mode.") : Mage::helper( 'lunar_payment' )->__("The live private key is not valid or set to test mode.");

            Mage::throwException($message);
        }

        return $this;
    }

    public function validateApiKey()
    {
        /** Check if the new value is empty. */
        if (!self::$apiKey) {
            return $this;
        }

        $publicKeys = self::$mode === Lunar_Payment_Model_Source_PaymentMode::TEST_MODE_VALUE ? self::$testPublicKeys : self::$livePublicKeys;

        /** Check if we have saved any validation live public keys. */
        if (empty($publicKeys)) {
            $message = self::$mode === Lunar_Payment_Model_Source_PaymentMode::TEST_MODE_VALUE ? Mage::helper('lunar_payment')->__("The test public key doesn't seem to be valid.") : Mage::helper('lunar_payment')->__("The live public key doesn't seem to be valid.");

            Mage::throwException($message);
        }

        /** Check if the public key is exists among the saved ones. */
        if (!in_array(self::$apiKey, $publicKeys)) {
            $message = self::$mode === Lunar_Payment_Model_Source_PaymentMode::TEST_MODE_VALUE ? Mage::helper('lunar_payment')->__("The test public key doesn't seem to be valid.") : Mage::helper('lunar_payment')->__("The live public key doesn't seem to be valid.");

            Mage::throwException($message);
        }

        return $this;
    }

    protected function fetchLiveKeys($lunarClient, $identity)
    {
        $merchants = $lunarClient->merchants()->find($identity);

        if ($merchants) {
            foreach ($merchants as $merchant) {
                if (!$merchant['test']) {
                    self::$livePublicKeys[] = $merchant['key'];
                }
            }
        }
    }

    protected function fetchTestKeys($lunarClient, $identity)
    {
        $merchants = $lunarClient->merchants()->find($identity);

        if ($merchants) {
            foreach ($merchants as $merchant) {
                if ($merchant['test']) {
                    self::$testPublicKeys[] = $merchant['key'];
                }
            }
        }
    }

    public function setAppKey($appKey)
    {
        self::$appKey = $appKey;

        return $this;
    }

    public function setApiKey($apiKey)
    {
        self::$apiKey = $apiKey;

        return $this;
    }

    public function setMode($mode)
    {
        self::$mode = $mode;

        return $this;
    }
}