<?php

/**
 *
 */
class Lunar_Payment_MobilepayController extends Mage_Core_Controller_Front_Action
{
    const REMOTE_URL = 'https://b.paylike.io';
    const PLUGIN_VERSION = '1.0.0';

    private $mobilePayCode = 'lunarmobilepay';
    private $hintsOrderKey = 'lunarmobilepay_hints';

    private $order;
    private $orderId = null;
    private $args = [];
    private $referer = '';
    private $orderBaseUrl = '';
    private $beforeOrder = true;
    private $authorizationId = '';
    private $isInstantMode = false;

    /**
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs
     */
    public function __construct(
            Zend_Controller_Request_Abstract $request,
            Zend_Controller_Response_Abstract $response,
            array $invokeArgs = []
        ){
        parent::__construct($request, $response, $invokeArgs);

        $this->request = $request;
        $this->response = $response;
        $this->isInstantMode = (Lunar_Payment_Model_Lunarmobilepay::ACTION_AUTHORIZE_CAPTURE == $this->getStoreConfigValue('payment_action'));

        $orderId = $this->request->getParam('order_id');

        // $this->baseUrl = Mage::getBaseUrl(); // includes /index.php
        $this->baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        /**
         * If request has order_id, the request is from a redirect (after_order)
         */
        if ($orderId) {

            $this->orderId = $orderId;

            $this->beforeOrder = false;

            $this->order = Mage::getModel('sales/order')->load($this->orderId);

            $this->args = $this->getConfig();

            $this->referer = $this->baseUrl . 'lunar/mobilepay/?order_id=' . $this->orderId;
        }
        else {
            $this->args = $this->request->getParam('args');
            $this->args['amount']['exponent'] = (int) $this->args['amount']['exponent'];
        }

        return $this;
    }

    /**
     * EXECUTE
     */
    public function indexAction()
    {
        $this->getHintsFromOrder();

        $this->setArgs();

        $response = $this->mobilePayPayment();

        if (isset($response['error'])) {
            if ($this->beforeOrder) {
                return $this->sendJsonResponse($response);
            } else {
                /**
                 * @TODO - something not working for the moment
                 */
                $errorMessage = $response['error'] . '.<br> Please try again or contact system administrator. <a href="/">Go to homepage</a>';
                return $this->redirectToErrorPage($errorMessage);
            }
        }

        $this->authorizationId = isset($response['data']['authorizationId']) ? $response['data']['authorizationId'] : '';


        if($this->authorizationId) {
            /**
             * Before order, send json response to front component
             */
            if ($this->beforeOrder) {
                return $this->sendJsonResponse($response);
            }
            /**
             * After order, redirect to success after set trxid on quote payment and capture if instant mode.
             */
            if ($this->request->getParam('is_redirect')) {

                /** Update info on last order payment */
                $this->setTxnIdOnOrderPayment();
                $this->setTxnIdOnLunarTransaction();
                $this->updateLastOrderStatusHistory();

                if ($this->isInstantMode) {
                    // the order state will be changed after invoice creation
                    $this->createInvoiceForOrder();
                }
                else {
                    $this->order->setState(Mage_Sales_Model_Order::STATE_PROCESSING)->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
                }

                $this->order->save();

                return $this->_redirect('checkout/onepage/success');
            }
        }


        if (isset($response['data']['type']) && ($response['data']['type'] === 'redirect') ) {
            $dataRedirectUrl = $response['data']['url'] . urlencode(urlencode('&is_redirect=1')); // encode twice

            return $this->_redirectUrl($dataRedirectUrl);
        }

        /**
         * Redirect to error page if response is iframe & checkout mode is after_order
         */
        if (
            ! $this->beforeOrder
            && isset($response['data']['type'])
            && ($response['data']['type'] === 'iframe')
        ) {
            $errorMessage = 'An error occured in server response. <br> Please try again or contact system administrator. <a href="/">Go to homepage</a>';
            return $this->redirectToErrorPage($errorMessage);
        }

        return $this->sendJsonResponse($response);
    }

    /**
     * SET TXN ID ON ORDER PAYMENT
     */
    private function setTxnIdOnOrderPayment()
    {
        $orderPayment = $this->order->getPayment();

        $baseGrandTotal = $this->order->getBaseGrandTotal();
        $grandTotal = $this->order->getGrandTotal();

        $orderPayment->setBaseAmountAuthorized($baseGrandTotal);
        $orderPayment->setAmountAuthorized($grandTotal);
        $orderPayment->setLunarTransactionId($this->authorizationId);
        $orderPayment->setLastTransId($this->authorizationId);
        $orderPayment->save();

        /** Manually insert transaction if after_order & delayed mode. */
        if (! $this->beforeOrder && ! $this->isInstantMode) {
            $this->insertNewTransactionForPayment($orderPayment);
        }
    }

    /**
     * INSERT NEW TRANSACTION FOR PAYMENT
     */
    private function insertNewTransactionForPayment($orderPayment)
    {
        /**
         * @TODO can we use _authorize from Payment model ? (inherited by our model)
         * @see Mage_Sales_Model_Order_Payment L1070 -> ...
         * in that case we can remove some of the methods here and use only one (?)
         */
        $orderPayment->setTransactionId($this->authorizationId);
        $orderPayment->setIsTransactionClosed(0);
        $orderPayment->setShouldCloseParentTransaction(0);
        //  $paymentTransaction = $orderPayment->_addTransaction('authorization', null, true); // true - failsafe
         $paymentTransaction = $orderPayment->addTransaction('authorization');
    }

    /**
     * SET TXN ID ON ORDER PAYMENT
     */
    private function setTxnIdOnLunarTransaction()
    {
        $data = array(
			'lunar_tid'       => $this->authorizationId,
			'order_id'        => $this->orderId,
			'payed_at'        => date( 'Y-m-d H:i:s' ),
			'payed_amount'    => $this->args['amount']['value'],
			'refunded_amount' => 0,
			'captured'        => 'NO'
		);

		$lunarTransactionModel = Mage::getModel( 'lunar_payment/lunaradmin' );

		try {
			$lunarTransactionModel->setData($data)->save();
			return $this;

		} catch ( Exception $e ) {

			$errormsg = Mage::helper( 'lunar_payment' )->__( $e->getMessage() );
			Mage::throwException( $errormsg );

		}
    }

    /**
     * UPDATE LAST ORDER STATUS HISTORY
     */
    private function updateLastOrderStatusHistory()
    {
        $statusHistories = $this->order->getStatusHistoryCollection()->toArray()['items'];

        /** Get only last created history */
        $orderHistory = $statusHistories[0];

        $fakeTxnId = Mage::helper('lunar_payment')::FAKE_TXN_ID;
        $commentContentModified = str_replace($fakeTxnId, $this->authorizationId, $orderHistory['comment']);

        $historyItem = $this->order->getStatusHistoryById($orderHistory['entity_id']);

        /** Delete last order status history if conditions met. */
        if ($historyItem) {

            if ( ! $this->beforeOrder && $this->isInstantMode) {
                $historyItem->delete();
                return;
            }
            /** Update last order status history if conditions met. */
            if ($historyItem && ! $this->beforeOrder && ! $this->isInstantMode) {
                $baseGrandTotal = $this->order->getBaseGrandTotal();
                $formattedPrice = Mage::helper('core')->currency($baseGrandTotal, $format = true, $includeContainer = false);
                /** The price will be displayed in base currency. */
                $commentContentModified = 'Authorized amount of ' . $formattedPrice . '. Transaction ID: "' . $this->authorizationId . '".';
                $historyItem->setIsCustomerNotified(0); // @TODO check this (is notified? should we notify?)
            }

            $historyItem->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
            $historyItem->setComment($commentContentModified);
            $historyItem->save();
        }
    }

    /**
     * SET ARGS
     */
    private function setArgs()
    {
        $publicKey = $this->getStoreConfigValue('live_public_key');

        if ('test' == $this->getStoreConfigValue('payment_mode')) {
            $publicKey = $this->getStoreConfigValue('test_public_key');
            $this->args['test'] = new \stdClass();
        }

        $this->args['integration'] = ['key' => $publicKey];

        $this->args['mobilepay'] = [
            'configurationId' => $this->getStoreConfigValue('configuration_id'),
            'logo'            => $this->getStoreConfigValue('logo_url'),
        ];

        if ($this->referer) {
            $returnUrl = $this->referer;
        } else {
            /** Checkout payment step url */
            $returnUrl = $this->_redirectReferer();
        }

        if ($returnUrl && !$this->beforeOrder) {
            $this->args['mobilepay']['returnUrl'] = $returnUrl;
        }

        $this->args['hints'] = isset($this->args['hints']) ? $this->args['hints'] : [];
    }

    /**
     * MOBILEPAY PAYMENT
     */
    private function mobilePayPayment()
    {
        /**
        * Request
        */
        $response = $this->request('/payments');

        if ( ! $response) {
            return $this->error('There was an error. Please try again later');
        }

        if (isset($response['authorizationId'])) {
            return $this->success($response);
        }


        if (!isset($response['challenges'])) {
            return $this->error('Payment failed');
        }

        $challengeResponse = $this->handleFirstChallenge($response['challenges']);


        if (isset($challengeResponse['error'])) {
            return $this->error('There was an error. Please try again later');
        }

        if (!$challengeResponse) {
            return $this->mobilePayPayment();
        }

        $challengeResponse['hints'] = $this->args['hints'];

        return $this->success($challengeResponse);
    }

    /**
     * ERROR
     */
    private function error($message)
    {
        return ['error' => $message];
    }

    /**
     * SUCCESS
     */
    private function success($data)
    {
        return ['data' => $data];
    }

    /**
     * HANDLE FIRST CHALLENGE
     */
    protected function handleFirstChallenge($challenges)
    {
        $challenge = $challenges[0]; // we prioritize the first one always

        if (count($challenges) > 1) {
            if ($this->beforeOrder) {
                $challenge = $this->searchForChallenge($challenges, 'iframe');
            } else {
                $challenge = $this->searchForChallenge($challenges, 'redirect');
            }

            if (!$challenge) {
                $challenge = $challenges[0];
            }
        }

        /**
         * Request
         */
        $response = $this->request($challenge['path']);

        if (isset($response['error'])) {
            return $this->error($response['error']);
        }

        if (isset($response['code']) && isset($response['message'])) {
            return $this->error($response['message']);
        }

        if ( ! isset($response['hints'])) {
            $notBefore = \DateTime::createFromFormat('Y-m-d\TH:i:s+', $data['notBefore']);
			$now = new \DateTime();
			$timeDiff = ($notBefore->getTimestamp() - $now->getTimestamp()) + 1; // add 1 second to account for miliseconds loss

            if ($timeDiff > 0) {
                sleep($timeDiff);
            }

            return $this->handleFirstChallenge($challenges);
        }

        $this->args['hints'] = array_merge($this->args['hints'], $response['hints']);

        $this->saveHintsOnOrder();

        switch ($challenge['type']) {
            case 'fetch':
            case 'poll':
                return [];
                break;

            case 'redirect':
                $response['type'] = $challenge['type'];
                // store hints for this order for 30 minutes
                return $response;
                break;

            case 'iframe':
            case 'background-iframe':
                $response['type'] = $challenge['type'];
                return $response;
                break;

            default:
                return $this->error('Unknown challenge type: ' . $challenge['type']);
        }

        return $response;
    }


    /**
     * SEARCH FOR CHALLENGE
     */
    protected function searchForChallenge($challenges, $type)
    {
        foreach ($challenges as $challenge) {
            if ($challenge['type'] === $type) {
                return $challenge;
            }
        }

        return false;
    }

    /**
     * REQUEST
     */
    protected function request($path, $requestMethod = Zend_Http_Client::POST)
    {
        $this->logDebug("Calling $path with hints:");
        $this->logDebug(isset($this->args['hints']) ? $this->args['hints'] : []);

        $params = [
            'headers' => [
                'Content-Type: application/json',
                'Accept-Version: 4',
            ],
            'version' => '1.0',
            'body'        => json_encode($this->args),
            'redirection' => 5,
            'timeout'     => 45,
            'blocking'    => true,
            'cookies'     => [],
            'allow_redirects' => true,
        ];

        $response = $this->makeCurlRequest($path, $params, $requestMethod);

        $this->logDebug("Response:");
        $this->logDebug($response);

        return $response;
    }


    /**
     * MAKE CURL REQUEST
     */
    private function makeCurlRequest(
        string $uriEndpoint,
        array $params = [],
        string $requestMethod = Zend_Http_Client::POST
    ) {
        try {

            $curl = new Varien_Http_Adapter_Curl();
            $curl->setConfig($params);
            $curl->write($requestMethod, self::REMOTE_URL . $uriEndpoint, '1.1', $params['headers'], $params['body']);
            $data = $curl->read();
            $resCode = Zend_Http_Response::extractCode($data);

            if ($data === false) {
                throw new \Exception('The server has no response. Please try again or contact system administrator.');
            }

            $data = preg_split('/^\r?$/m', $data, 2);
            $data = trim($data[1]);
            $curl->close();

            $response = json_decode($data, true);

        } catch (\Exception $exception) {
            $response = ['error' => $exception->getMessage()];
        }

        return $response;
    }

    /**
     *
     */
    private function getHintsFromOrder()
    {
        if (! $this->order) {
            return false;
        }

        $payment = $this->order->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        $orderHints = [];
        if ($additionalInformation && array_key_exists($this->hintsOrderKey, $additionalInformation)) {
            $orderHints = $additionalInformation[$this->hintsOrderKey];
        }

        if ($orderHints) {
            $this->args['hints'] = $orderHints;
        }
    }

    /**
     *
     */
    private function saveHintsOnOrder()
    {
        if (! $this->order) {
            return false;
        }

        // preserve already existing additional data
        $payment = $this->order->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        $additionalInformation[$this->hintsOrderKey] = $this->args['hints'];
        $payment->setAdditionalInformation($additionalInformation);
        $payment->save();

        $this->logDebug("Storing hints: " . json_encode($this->args['hints'], JSON_PRETTY_PRINT));
    }

    /**
     *
     */
    private function createInvoiceForOrder()
    {
        $invoiceEmailMode =  $this->getStoreConfigValue('invoice_email');

        try {
            $invoicesArray = $this->order->getInvoiceCollection()->toArray();

            if ($invoicesArray['totalRecords']) {
                return null;
            }

            if (!$this->order->canInvoice()) {
                return null;
            }

            $invoice = Mage::getModel('sales/service_order', $this->order)->prepareInvoice();

            // $invoice->setTransactionId($this->authorizationId);
            // // $invoice->save();

            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);

            $transactionSave = Mage::getModel('core/resource_transaction')
                                ->addObject($invoice)
                                ->addObject($invoice->getOrder());
            $transactionSave->save();

            if (!$invoice->getEmailSent() && $invoiceEmailMode == 1) {
                try {
                    $invoice->sendEmail();
                } catch (\Exception $e) {
                    $this->logDebug('Unable to send email. Reason: ' . $e->getMessage());
                    // Do something if failed to send
                }
            }
        } catch (\Exception $e) {
            /** Set payment review if any error */
            $this->order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW)->setStatus(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
            $this->order->addStatusHistoryComment('Exception message: ' . $e->getMessage(), false);
            $this->order->save();
        }
    }

    /**
     * GET CONFIG
     * We are able to build config in backend for both after or before order flow
     */
    private function getConfig()
    {
        if ( ! $this->order) {
            return;
        }

        $quoteId = $this->order->getQuoteId();
        $quote = Mage::getModel('sales/quote')->load($quoteId);

        $items = $quote->getAllItems();

        $products = [];
        $count = 0;
        $products[0] = $count;
        foreach ($items as $itemId => $item) {
            /**
             * Exclude shipping item (does not have relevant data)
             * We'll add later from shipping address as separate item
             */
            if ($item->getParentItemId()) {
                continue;
            }

            $count++;

            $products[$count] = [
				'ID'          => $item->getProductId(),
				'SKU'         => $item->getSku(),
				'Name'        => str_replace( "'", "&#39;", $item->getName() ),
				'Quantity'    => $item->getQty(),
                'Unit Price'  => $item->getPriceInclTax(),
                'Total Price' => $item->getRowTotalInclTax(),
            ];
        }

        $products[0] = $count;

        $lunar_helper = Mage::helper( 'lunar_payment' );

        $address = $quote->getBillingAddress();
        $email = $address->getEmail();
        $name = $address->getName();

        if ( ! $email ) { $email = $quote->getCustomerEmail(); }
        if ( ! $name ) { $name = $quote->getCustomerName(); }

        if ( ! $address) {
            $address = $quote->getShippingAddress();
        }

        $currenciesHelper = new Lunar_Data_Currencies();
        $currency = $quote->getQuoteCurrencyCode();
        $amountInMinor = (int) $currenciesHelper->ceil($quote->getGrandTotal(), $currency);

        $customerAddress = $address->getStreet()[0] . ", "
                        . $address->getCity() . ", "
                        . $address->getRegion() . " "
                        . $address->getPostcode() . ", "
                        . $address->getCountryId();

        return [
            'amount' => [
                'currency' => $currency,
                'value' => $amountInMinor,
                'exponent' => $currenciesHelper->getCurrency($currency)['exponent'],
            ],
            'custom' => [
                'orderId' => $this->order->getIncrementId(),
                'products' => $products,
                'shipping_tax' => $quote->getShippingAddress()->getShippingInclTax(),
                'customer' => [
                    'name' => $name,
                    'email' => $email,
                    'phoneNo' => $address->getTelephone(),
                    'address' => $customerAddress,
                    'IP' => $quote->getRemoteIp(),
                ],
                'locale' => Mage::app()->getLocale()->getLocaleCode(),
                'platform' => [
                    'name' => 'Magento',
                    'version' => Mage::getVersion(),
                ],
				'pluginVersion' => self::PLUGIN_VERSION,
            ],
        ];
    }

    /**
     *
     */
    private function logDebug($message)
    {
        Mage::log($message, Zend_Log::DEBUG, 'lunarmobilepay_debug.log');
    }

	/**
	 *
	 */
	private function getStoreConfigValue($key)
	{
		return Mage::getStoreConfig('payment/' . $this->mobilePayCode . '/' . $key);
	}

    /**
     * Method responsible to show error message if payment fails
     */
    public function displayerrorAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle("Mobilepay error");
        $this->getLayout()->getBlock("root")->setTemplate('page/1column.phtml');
        $this->renderLayout();
    }

    /**
     * Set session error and redirect to custom page
     */
    private function redirectToErrorPage($errorMessage)
    {
        Mage::getSingleton('core/session')->addError($errorMessage);
        return $this->_redirect('lunar/mobilepay/displayerror');
    }

    /**
     *
     */
    private function sendJsonResponse($response, $code = 200)
    {
        return $this->getResponse()
                ->clearHeaders()
                ->setHeader('HTTP/1.1', $code, true)
                ->setHeader('Content-type','application/json', true)
                ->setBody(Mage::helper('core')->jsonEncode($response));
    }
}
