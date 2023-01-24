<?php


class Lunar_Payment_Model_Lunar extends Lunar_Payment_Model_LunarPaymentAbstract
{
	const PAYMENT_CODE = 'lunar';
	protected $_code = self::PAYMENT_CODE;

	protected $_formBlockType = 'lunar_payment/form_lunar';
	protected $_infoBlockType = 'lunar_payment/info_lunar';

	protected $logFileName = self::PAYMENT_CODE . '.log';
}