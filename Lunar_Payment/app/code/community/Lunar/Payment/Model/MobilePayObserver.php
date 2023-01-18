<?php

class Lunar_Payment_Model_MobilePayObserver
{
    /**
     * observe checkout_submit_all_after event
     *
     * @param Varien_Event_Observer $observer
     *
     * @throws Varien_Exception
     */
    public function redirectAfterOrder(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if ($order && $order->getId()) {
            $payment = $order->getPayment();

            if ($payment && ('lunarmobilepay' == $payment->getMethod())) {

                /** We modify the flow only in after order scenario. */
                if ('after_order' == Mage::getStoreConfig('payment/lunarmobilepay/checkout_mode')) {
                    $orderId = $order->getId();

                    $url = Mage::getUrl('lunar/mobilepay/');

                    Mage::getSingleton('checkout/session')->setRedirectUrl($url . '?order_id=' . $orderId);
                }
            }
        }

        return $this;
    }

    /**
     * observe sales_order_payment_place_start event
     *
     * @param Varien_Event_Observer $observer
     *
     * @throws Varien_Exception
     */
    public function preventOrderInvoice(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();

        if ($payment && ('lunarmobilepay' == $payment->getMethod())) {

            /** Modify (on the fly) the flow in after order scenario & if placeholder exists. */
            if (
                'after_order' == Mage::getStoreConfig('payment/lunarmobilepay/checkout_mode')
                && Mage::helper('lunar_payment')::FAKE_TXN_ID == $payment->getLunarTransactionId()
            ) {
                Mage::app()->getStore()->setConfig('payment/lunarmobilepay/payment_action', '');
                Mage::app()->getStore()->setConfig('payment/lunarmobilepay/order_status', 'pending');
            }
        }

        return $this;
    }

    /**
     * observe sales_order_save_before event
     *
     * @param Varien_Event_Observer $observer
     *
     * @throws Varien_Exception
     */
    public function changeOrderState(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();

        if ($payment && ('lunarmobilepay' == $payment->getMethod())) {

            if (
                'before_order' == Mage::getStoreConfig('payment/lunarmobilepay/checkout_mode')
                && 'authorize' == Mage::getStoreConfig('payment/lunarmobilepay/payment_action')
            ) {
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING)
                    ->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
            }
        }

        return $this;
    }

    /**
	 * Adds a button to sales order view layout
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @throws Varien_Exception
	 */
	public function addPaymentButton(Varien_Event_Observer $observer)
    {
        $layout = Mage::app()->getLayout();
        $block = $layout->getBlock('sales.order.info.buttons');

        $order = $block->getOrder();
        $payment = $order->getPayment();

        if (
            $payment
            && ('lunarmobilepay' == $payment->getMethod())
            && is_null($payment->getAmountPaid())
            && is_null($payment->getAmountAuthorized())
        ) {

            $buttonText = '';
            $orderHints = $this->getAdditionalInfoFromPayment($payment, 'lunarmobilepay_hints');
            if ($orderHints) {
                $buttonText = 'Finalize order';
            } else {
                $buttonText = 'Pay Now';
            }

            $orderId = $order->getId();
            $url = Mage::getUrl('lunar/mobilepay/');
            $payUrl = $url . '?order_id=' . $orderId;

            $payLinkHtml = '<a href="' . $payUrl . '" target="_blank" class="button" style="margin-bottom:10px;">' . $buttonText . '</a>';

            $block = $layout->createBlock('core/text');
            $block->setText(
                '<script type="text/javascript">
                    jQuery(document).ready(() => {
                        jQuery(".order-info").before(\'' . $payLinkHtml . '\');
                    });
                </script>'
            );
            $layout->getBlock('head')->append($block);

        }
	}

    /**
     *
     */
    private function getAdditionalInfoFromPayment($payment, $key)
    {
        $additionalInformation = $payment->getAdditionalInformation();
        $data = [];
        if ($additionalInformation && array_key_exists($key, $additionalInformation)) {
            $data = $additionalInformation[$key];
        }

        return $data;
    }

}
