<?php

class Lunar_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{
	const FAKE_TXN_ID = 'trxid_placeholder';

	private $paymentMethodCode = 'lunar';

	/**
	 *
	 */
	public function setPaymentMethodCode($methodCode)
	{
		$this->paymentMethodCode = $methodCode;
	}

	/**
	 *
	 */
	public function getStatus() {
		return $this->getStoreConfigData('status' );
	}

	/**
	 *
	 */
	public function getCheckoutMode() {
		return $this->getStoreConfigData('checkout_mode' );
	}

	/**
	 * @return mixed
	 */
	public function getPaymentMethodDescription() {
		return $this->getStoreConfigData('description' );
	}

	/**
	 * @return string
	 */
	public function getAlertOnNotReady() {
		return ( Mage::helper( 'payment' )->__( 'The payment data is not ready, wait for all parts to be loaded.' ) );
	}

	/**
	 * @return mixed
	 */
	public function getPopupTitle() {
		return $this->getStoreConfigData('pop_up_title' );
	}

	public function getLogsEnabled() {
		return $this->getStoreConfigData('logs_enabled');
	}


	/**
	 * @return mixed
	 */
	public function getPublicKey() {
		if ( $this->getPaymentMode() == 'test' ) {
			return $this->getStoreConfigData('test_public_key' );
		}

		return $this->getStoreConfigData('live_public_key' );
	}

	/**
	 * @return mixed
	 */
	public function getPaymentMode() {
		return $this->getStoreConfigData('payment_mode' );
	}

	/**
	 * @param bool $json
	 *
	 * @return array
	 * @throws Varien_Exception
	 */
	public function getProducts( $json = false ) {
		$products_array = array();
		$products       = Mage::getSingleton( 'checkout/session' )->getQuote()->getAllVisibleItems();
		foreach ( $products as $product ) {
			$name             = $product->getData( 'name' );
			$products_array[] = array(
				'ID'       => $product->getData( 'item_id' ),
				'SKU'      => $product->getData( 'sku' ),
				'Name'     => str_replace( "'", "&#39;", $name ),
				'Quantity' => $product->getData( 'qty' )
			);
		}
		if ( $json ) {
			$products_array = Mage::helper( 'core' )->jsonEncode( $products_array );
		}

		return $products_array;
	}

	/**
	 * @return mixed
	 */
	public function getTelephone() {
		return Mage::helper( 'checkout/cart' )->getQuote()->getShippingAddress()->getData( 'telephone' );
	}

	/**
	 * @return mixed|string
	 */
	public function getAddress() {
		$customer_address = Mage::helper( 'checkout/cart' )->getQuote()->getShippingAddress()->getData();
		$street           = trim( preg_replace( '/\s+/', ' ', $customer_address['street'] ) );
		$city             = $customer_address['city'];
		$region           = $customer_address['region'];
		$country_code     = $customer_address['country_id'];
		$country          = Mage::app()->getLocale()->getCountryTranslation( $country_code );
		$postcode         = $customer_address['postcode'];
		$address          = $street . ', ' . $city . ', ' . $region . ', ' . $country . ', ' . $postcode;
		$address          = str_replace( ', ,', ',', $address );

		return $address;
	}

	/**
	 * @param bool $json
	 *
	 * @return mixed
	 */
	public function getCreditCardLogos( $json = false ) {
		if ( $json ) {
			$logos       = $this->getStoreConfigData('payment_logo' );
			$logos_array = explode( ',', $logos );

			return Mage::helper( 'core' )->jsonEncode( $logos_array );
		} else {
			return $this->getStoreConfigData('payment_logo' );
		}
	}

	/**
	 *
	 */
	public function getCheckoutMobilePayLogoUrl() {
		return Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_SKIN ) . 'frontend/lunar/logos/mobilepay-logo.png';
	}

	/**
	 *
	 */
	private function getStoreConfigData($key)
	{
		return Mage::getStoreConfig('payment/' . $this->paymentMethodCode . '/' . $key);
	}

}
