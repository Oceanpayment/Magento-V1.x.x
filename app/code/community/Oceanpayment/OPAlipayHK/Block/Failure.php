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
 * @package 	Oceanpayment_AlipayHK
 * @copyright	Copyright (c) 2009 Oceanpayment,LLC. (http://www.oceanpayment.com)
 */

class Oceanpayment_OPAlipayHK_Block_Failure extends Mage_Core_Block_Template
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
		 
		$this->setTemplate('op_alipayhk/failure.phtml');
	
		return parent::_toHtml();
	}
	
	
	public function getRealOrderId()
	{
		return Mage::getSingleton('checkout/session')->getLastRealOrderId();
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
	
	public function getPaymentDetails()
	{
		return Mage::getSingleton('checkout/session')->getData('payment_details');
	}
	
	public function getPaymentSolutions()
	{
		return Mage::getSingleton('checkout/session')->getData('payment_solutions');
	}
}
