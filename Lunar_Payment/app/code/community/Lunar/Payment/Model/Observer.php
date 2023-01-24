<?php

class Lunar_Payment_Model_Observer {
	/**
	 * To fetch the updated cart total based on the shipment and discount coupon specially for Magestore checkout
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @throws Varien_Exception
	 */
	public function updatedGrandTotal( Varien_Event_Observer $observer ) {
		$block = $observer->getBlock();
		if ( ( $block->getNameInLayout() == 'review_info' ) && ( $child = $block->getChild( 'lunar.updated.total.block' ) ) ) {
			$transport = $observer->getTransport();
			if ( $transport ) {
				$html = $transport->getHtml();
				$html .= $child->toHtml();
				$transport->setHtml( $html );
			}
		}
	}

}
