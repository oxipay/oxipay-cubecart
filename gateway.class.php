<?php
/**
 * CubeCart v6
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2014. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@cubecart.com
 * License:  GPL-3.0 http://opensource.org/licenses/GPL-3.0
 */
class Gateway {
	private $_config;
	private $_module;
	private $_basket;
	private $_url;

	public function __construct($module = false, $basket = false) {
		$this->_db	=& $GLOBALS['db'];

		$this->_module	= $module;
		$this->_basket =& $GLOBALS['cart']->basket;
		$this->_url	= $this->_module['testMode'] ? 'https://xpozsecure.certegyezipay.com.au/Checkout?platform=Default' : 'https://secure.oxipay.com.au/Checkout?platform=Default';
	}

	##################################################

	public function transfer() {
		$transfer	= array(
			'action'	=> $this->_url,
			'method'	=> 'post',
			'target'	=> '_self',
			'submit'	=> 'auto'
		);
		return $transfer;
	}

	##################################################

	public function repeatVariables() {
		return false;
	}

	public function fixedVariables() {

		$hidden = array(
			'x_account_id' => $this->_module['mid'],
			'x_reference' => $this->_basket['cart_order_id'],
			'x_amount' => $this->_basket['total'],
			'x_currency' => $GLOBALS['config']->get('config', 'default_currency'),
			'x_url_callback' => $GLOBALS['storeURL'].'/index.php?_g=remote&type=gateway&cmd=call&module=Oxipay',
			'x_url_complete' => $GLOBALS['storeURL'].'/index.php?_g=remote&type=gateway&cmd=process&module=Oxipay',
			'x_url_cancel' => $GLOBALS['storeURL'].'/index.php?_a=checkout',
			'x_shop_country' => getCountryFormat($GLOBALS['config']->get('config', 'store_country'), 'numcode', 'iso'),
			'x_shop_name' => $GLOBALS['config']->get('config', 'store_name'),
			'x_test' => $this->_module['testMode'] ? 'true' : 'false',
			'x_customer_phone' => $this->_basket['billing_address']['phone'],
			'x_customer_first_name' => $this->_basket['billing_address']['first_name'],
			'x_customer_last_name' => $this->_basket['billing_address']['last_name'],
			'x_customer_email' => $this->_basket['billing_address']['email'],
			'x_customer_billing_country' => $this->_basket['billing_address']['country_iso'],
			'x_customer_billing_city' => $this->_basket['billing_address']['town'],
			'x_customer_billing_address1' => $this->_basket['billing_address']['line1'],
			'x_customer_billing_address2' => $this->_basket['billing_address']['line2'],
			'x_customer_billing_state' => $this->_basket['billing_address']['state'],
			'x_customer_billing_zip' => $this->_basket['billing_address']['postcode'],
			'x_customer_shipping_country' => $this->_basket['delivery_address']['country_iso'],
			'x_customer_shipping_city' => $this->_basket['delivery_address']['town'],
			'x_customer_shipping_address1' => $this->_basket['delivery_address']['line1'],
			'x_customer_shipping_address2' => $this->_basket['delivery_address']['line2'],
			'x_customer_shipping_state' => $this->_basket['delivery_address']['state'],
			'x_customer_shipping_zip' => $this->_basket['delivery_address']['postcode'],
			'x_invoice' => $this->_basket['cart_order_id'],
			'x_description' => 'Payment for order '.$this->_basket['cart_order_id']);

		$hidden['x_signature'] = $this->oxipay_sign($hidden, $this->_module['api_key']);
			
		return (isset($hidden)) ? $hidden : false;
	}

	public function call() {
		$order				= Order::getInstance();
		$cart_order_id 		= $this->_basket['x_reference'];
		$order_summary		= $order->getSummary($cart_order_id);
		$response_code 		= (string)$_POST['x_result'];
			
		if($response_code=='completed'){	
			$order->orderStatus(Order::ORDER_PROCESS, $cart_order_id);
			$order->paymentStatus(Order::PAYMENT_SUCCESS, $cart_order_id);
		}

		$transData['notes']			= 'Test mode: '.$_POST['x_test'];
		$transData['order_id']		= $cart_order_id;
		$transData['trans_id']		= $_POST['x_gateway_reference'];
		$transData['amount']		= $_POST['x_amount'];
		$transData['extra']			= '';
		$transData['status']		= ucfirst((string)$_POST['x_result']);
		$transData['customer_id']	= $order_summary['customer_id'];
		$transData['gateway']		= $this->_module;
		$order->logTransaction($transData);
	}

	public function process() {
		httpredir(currentPage(array('_g', 'type', 'cmd', 'module'), array('_a' => 'complete')));
	}

	##################################################

	public function form() {
		return false;
	}

	function oxipay_sign($query, $api_key)
    {
        $clear_text = '';
        ksort($query);
        foreach ($query as $key => $value) {
            if (substr($key, 0, 2) === "x_") {
                $clear_text .= $key . $value;
            }
        }
        $hash = hash_hmac( "sha256", $clear_text, $api_key);
        return str_replace('-', '', $hash);
    }
	
}