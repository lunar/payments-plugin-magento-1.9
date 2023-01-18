<?php

class Lunar_Payment_Model_Source_PaymentLogos {

    public function toOptionArray() {
        $lunar_logos = Mage::getModel('lunar_payment/lunarlogos')
                ->getCollection()
                ->getData();

        $logo_array = array();
        foreach ($lunar_logos as $logo) {
            $data = array(
                'value' => $logo['file_name'],
                'label' => Mage::helper('lunar_payment')->__($logo['name'])
            );
            array_push($logo_array, $data);
        }

        return $logo_array;
    }

}
