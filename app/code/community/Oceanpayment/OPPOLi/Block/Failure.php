<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category	Oceanpayment
 * @package 	Oceanpayment_POLi
 * @copyright	Copyright (c) 2009 Oceanpayment,LLC. (http://www.oceanpayment.com)
 */

class Oceanpayment_OPPOLi_Block_Failure extends Mage_Core_Block_Template
{
	
	
	/**
	 * @deprecated after 1.4.0.1
	 */
	private $_order;
	
	/**
	 * toHtml
	 *
	 * @return string
	 * @deprecated after 1.4.0.1
	 */
	public function _toHtml()
	{
		 
		$this->setTemplate('op_poli/failure.phtml');
	
		return parent::_toHtml();
	}
	
	
	public function getRealOrderId()
	{
		return Mage::getSingleton('checkout/session')->getLastRealOrderId();
	}

	/**
	 *  响应代码解决方案
	 *
	 *  @return	  string
	 */
	public function getActionMessage()
	{
		
		//获取配置的trans_response_code
		$standard = Mage::getModel('oppoli/payment');
		$code_mode = $standard->getConfigData('trans_response_code');
		
		if($code_mode == 1){
			//获取线上的响应代码解决方案信息
			$oceanpayment_url = 'http://www.oceanpayment.com.cn/TransResponseCode.php';
			
			$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
			
			$data = array(
						'code' => $_SESSION['errorCode'],
						'lang' => $lang
					);
	
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_URL,$oceanpayment_url);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_TIMEOUT,5);
			
			if (curl_errno($ch)) {
				//超时则获取插件本身
				$op_actionMsg = $this->getLocalMessage();
			}else{
				$op_actionMsg = curl_exec($ch);
			}
			
		}elseif($code_mode == 0){
			//获取插件本身的的响应代码解决方案信息
			$op_actionMsg = $this->getLocalMessage();
		}
		

		return $op_actionMsg;
	}


	/**
	 *  获取插件本身的的响应代码解决方案信息
	 *	更新日期2015-04-12
	 *  @return	  string
	 */
	public function getLocalMessage()
	{
		
		$CodeAction = array(
				'80010' => $this->__('1. Try to Pay again, your card issuer may accept your payment.<br>2. Call the 800 number on the back of the card, ask your card issuer accept your payment, then pay again.<br>3. Change another card to pay.'),
				'80011' => $this->__('1. Try to Pay again, your card issuer may accept your payment.<br>2. Call the 800 number on the back of the card, ask your card issuer accept your payment, then pay again.<br>3. Change another card to pay.'),
				'80012' => $this->__('1. Try to Pay again, your card issuer may accept your payment.<br>2. Call the 800 number on the back of the card, ask your card issuer accept your payment, then pay again.<br>3. Change another card to pay.'),
				'80013' => $this->__('1. Try to Pay again, your card issuer may accept your payment.<br>2. Call the 800 number on the back of the card, ask your card issuer accept your payment, then pay again.<br>3. Change another card to pay.'),
				'80014' => $this->__('Contact the merchant website to find out how to do.'),
				'80020' => $this->__('Contact the merchant website to confirm the transaction amount.'),
				'80021' => $this->__('1. Please confirm the card number is right.<br>2. Change another card to pay.'),
				'80022' => $this->__('1. Please confirm the card has enough money or use another card.<br>2. Change another card to pay.'),
				'80023' => $this->__('Please confirm the card period of use.'),
				'80024' => $this->__('Please input the right PIN.'),
				'80025' => $this->__('1. Try to Pay again, your card issuer may accept your payment.<br>2. Call the 800 number on the back of the card, ask your card issuer accept your payment, then pay again.<br>3. Change another card to pay.'),
				'80026' => $this->__('Please input the right 3-digital CVV2/CSC.'),
				'80027' => $this->__('1. Please contact your card issuer to fix the problem.<br>2. Change another card to pay.'),
				'80028' => $this->__('1. Please call your card issuer to confirm your account is valid.<br>2. Change another card to pay.'),
				'80030' => $this->__('1. Try to Pay again, your card issuer may accept your payment.<br>2. Call the 800 number on the back of the card, ask your card issuer accept your payment, then pay again.<br>3. Change another card to pay.'),
				'80031' => $this->__('1. Contact the merchant website to confirm the payment result.<br>2. Try to pay again.'),
				'80032' => $this->__('Please contact the merchant website.'),
				'80033' => $this->__('Please contact the merchant website.'),
				'80034' => $this->__('Please contact the merchant website.'),
				'80035' => $this->__('Please contact the merchant website.'),
				'80036' => $this->__('Change another card to pay.'),
				'80037' => $this->__('Please contact the merchant website.'),
				'80050' => $this->__('Please contact the payment service company.'),
				'80051' => $this->__('Try to pay again.'),
				'80054' => $this->__('Please contact the merchant website.'),
				'80061' => $this->__('Please contact the merchant website.'),
				'80062' => $this->__('Please contact the merchant website.'),
				'80063' => $this->__('Please contact the merchant website.'),
				'80064' => $this->__('Please contact the merchant website.'),
				'80090' => $this->__('Cannot Start 3D Authorized Service.'),
				'80091' => $this->__('Input right 3D secure code or change another card to pay.'),
				'80092' => $this->__('Fail to process the 3D transaction techonolly.'),
				'80100' => $this->__('1. Try again, finish the transaction correctly.<br>2. Contact website with screenshot if it has error.'),
				'80101' => $this->__('Pay again or change another bank account to pay.'),
				'80120' => $this->__('Try again later or to  choose different payment scheme or bank account.'),
				'80121' => $this->__('Try again later or to  choose different payment scheme or bank account.'),
				'80200' => $this->__('Try to pay again and finish the payment.'),
		);
		
		
		return $CodeAction[$_SESSION['errorCode']];
		
	}
	
	
	
	
	/**
	 * Continue shopping URL
	 *
	 *  @return	  string
	 */
	public function getContinueShoppingUrl()
	{
		return Mage::getUrl('checkout/cart');
	}
}
