<?php


class Lunar_Payment_Model_Lunarmobilepay extends Lunar_Payment_Model_LunarPaymentAbstract
{
	const PAYMENT_CODE = 'lunarmobilepay';
	protected $_code = self::PAYMENT_CODE;

	protected $_formBlockType = 'lunar_payment/form_lunarmobilepay';
	protected $_infoBlockType = 'lunar_payment/info_lunarmobilepay';

	protected $logFileName = self::PAYMENT_CODE . '.log';
}