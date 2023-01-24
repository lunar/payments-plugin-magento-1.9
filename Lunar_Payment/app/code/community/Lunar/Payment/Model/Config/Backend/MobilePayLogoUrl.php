<?php

class Lunar_Payment_Model_Config_Backend_MobilePayLogoUrl extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $logoUrl = $this->getValue();
        $allowedExtensions = ['png', 'jpg', 'jpeg'];

        try {
            $fileSpecs = getimagesize($logoUrl);
        } catch (\Exception $e) {
            Mage::throwException('Cannot retrive an image by the url provided. Please check if the url is correct or insert another one.');
        }

        $fileMimeType = explode('/', $fileSpecs['mime'] ?? '');
        $fileExtension = end($fileMimeType);

        // $fileDimensions = ($fileSpecs[0] ?? '') . 'x' . ($fileSpecs[1] ?? '');
        // strcmp('250x250', $fileDimensions) !== 0      // disabled for the moment

        if (! preg_match('/^https:\/\//', $logoUrl)) {
            /** Mark the new value as invalid */
            $this->_dataSaveAllowed = false;
			Mage::throwException('The image url must begin with https://.');
		}
        if (! in_array($fileExtension, $allowedExtensions)) {
            /** Mark the new value as invalid */
            $this->_dataSaveAllowed = false;
			Mage::throwException('The image file must have one of the following extensions: ' . implode(', ', $allowedExtensions));
		}

        return parent::save();
    }
}