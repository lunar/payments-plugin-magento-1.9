<?php

class Lunar_Payment_IndexController extends Mage_Core_Controller_Front_Action {

	/**
	 * @throws Varien_Exception
	 */
	public function paymentAction() {
		$order_id       = $this->getRequest()->getParam( 'order_id' );
		$lunar_action = $this->getRequest()->getParam( 'lunar_action' );
		$orderObject    = Mage::getModel( 'sales/order' )->load( $order_id );
		$order_data     = $orderObject->getData();

		$response = array();
		switch ( $lunar_action ) {
			case 'refund' :
				$lunar_amount_to_refund = $this->getRequest()->getParam( 'lunar_amount_to_refund' );

				$response = Mage::getModel( 'lunar_payment/lunar' )->refund( $orderObject->getPayment(), $lunar_amount_to_refund, true );
				break;
			case 'void' :
				$response = Mage::getModel( 'lunar_payment/lunar' )->void( $orderObject->getPayment(), true );
				break;
		}

		$jsonData = Mage::helper( 'core' )->jsonEncode( $response );
		$this->getResponse()->setHeader( 'Content-type', 'application/json' );
		$this->getResponse()->setBody( $jsonData );
	}

	/**
	 * @return bool
	 */
	public function uploadLogoAction() {
		$logo_name = $this->getRequest()->getParam( 'logo_name' );
		if ( empty( $logo_name ) ) {
			$response = array(
				'status'  => 0,
				'message' => 'Please set a logo name.'
			);
			$jsonData = Mage::helper( 'core' )->jsonEncode( $response );
			$this->getResponse()->setHeader( 'Content-type', 'application/json' );
			$this->getResponse()->setBody( $jsonData );

			return false;
		}
		$logo_slug     = strtolower( str_replace( ' ', '-', $logo_name ) );
		$lunar_logos = Mage::getModel( 'lunar_payment/lunarlogos' )
		                     ->getCollection()
		                     ->addFieldToFilter( 'slug', $logo_slug )
		                     ->getFirstItem()
		                     ->getData();
		if ( ! empty( $lunar_logos ) ) {
			$response = array(
				'status'  => 0,
				'message' => 'The name added for the logo is already in use. Please try another name.'
			);
			$jsonData = Mage::helper( 'core' )->jsonEncode( $response );
			$this->getResponse()->setHeader( 'Content-type', 'application/json' );
			$this->getResponse()->setBody( $jsonData );

			return false;
		}

		if ( empty( $_FILES['logo_file']['name'] ) ) {
			$response = array(
				'status'  => 0,
				'message' => 'Please select a file for upload.'
			);
			$jsonData = Mage::helper( 'core' )->jsonEncode( $response );
			$this->getResponse()->setHeader( 'Content-type', 'application/json' );
			$this->getResponse()->setBody( $jsonData );

			return false;
		}
		$target_dir    = Mage::getBaseDir( 'skin' ) . Lunar_Payment_Model_Lunarlogos::PAYMENT_LOGO_PATH;
		$name          = basename( $_FILES['logo_file']["name"] );
		$path_parts    = pathinfo( $name );
		$extension     = $path_parts['extension'];
		$file_name     = $logo_slug . '.' . $extension;
		$target_file   = $target_dir . basename( $file_name );
		$imageFileType = pathinfo( $target_file, PATHINFO_EXTENSION );

		// Check if file already exists
		if ( file_exists( $target_file ) ) {
			$response = array(
				'status'  => 0,
				'message' => 'Sorry, file already exists.'
			);
			$jsonData = Mage::helper( 'core' )->jsonEncode( $response );
			$this->getResponse()->setHeader( 'Content-type', 'application/json' );
			$this->getResponse()->setBody( $jsonData );

			return false;
		}
		// Allow certain file formats
		if ( $imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "svg"
		) {
			$response = array(
				'status'  => 0,
				'message' => 'Sorry, only JPG, JPEG, PNG, GIF & SVG files are allowed.'
			);
			$jsonData = Mage::helper( 'core' )->jsonEncode( $response );
			$this->getResponse()->setHeader( 'Content-type', 'application/json' );
			$this->getResponse()->setBody( $jsonData );

			return false;
		}

		if ( ! move_uploaded_file( $_FILES['logo_file']["tmp_name"], $target_file ) ) {
			$response = array(
				'status'  => 0,
				'message' => 'Sorry, there was an error uploading your file.'
			);
			$jsonData = Mage::helper( 'core' )->jsonEncode( $response );
			$this->getResponse()->setHeader( 'Content-type', 'application/json' );
			$this->getResponse()->setBody( $jsonData );

			return false;
		}
		$save_data         = array(
			'name'         => $logo_name,
			'slug'         => $logo_slug,
			'file_name'    => $file_name,
			'default_logo' => '0',
			'created_at'   => date( 'Y-m-d H:i:s' )
		);
		$lunarLogosModel = Mage::getModel( 'lunar_payment/lunarlogos' );
		try {
			$res = $lunarLogosModel->setData( $save_data )->save();
			if ( $res ) {
				$response = array(
					'status'  => 1,
					'message' => "The file \"" . basename( $file_name ) . "\" has been uploaded."
				);
				$jsonData = Mage::helper( 'core' )->jsonEncode( $response );
				$this->getResponse()->setHeader( 'Content-type', 'application/json' );
				$this->getResponse()->setBody( $jsonData );

				return true;
			}
			unlink( $target_file );
			$response = array(
				'status'  => 0,
				'message' => "Oops! An error occurred while saving the logo. Please try again."
			);
			$jsonData = Mage::helper( 'core' )->jsonEncode( $response );
			$this->getResponse()->setHeader( 'Content-type', 'application/json' );
			$this->getResponse()->setBody( $jsonData );

			return false;

		} catch ( Exception $e ) {
			$response = array(
				'error'   => 1,
				'message' => $e->getMessage()
			);
			$jsonData = Mage::helper( 'core' )->jsonEncode( $response );
			$this->getResponse()->setHeader( 'Content-type', 'application/json' );
			$this->getResponse()->setBody( $jsonData );

			return false;
		}

	}
}
