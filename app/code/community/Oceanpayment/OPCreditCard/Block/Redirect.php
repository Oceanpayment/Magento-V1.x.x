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
 * @package 	Oceanpayment_CreditCard
 * @copyright	Copyright (c) 2009 Oceanpayment,LLC. (http://www.oceanpayment.com)
 */
class Oceanpayment_OPCreditCard_Block_Redirect extends Mage_Core_Block_Template
{
	/**
	 * Order instance
	 */
	protected $_order;
	
	protected $_processingArray = array('processing', 'complete');
	/**
	 *  Get order
	 *
	 *  @param    none
	 *  @return	  Mage_Sales_Model_Order
	 */
	public function getOrder()
	{
		
		if ($this->_order == null) {
			$session = Mage::getSingleton('checkout/session');
			$this->_order = Mage::getModel('sales/order');
			$this->_order->loadByIncrementId($session->getLastRealOrderId());
		}
		return $this->_order;
	}
	
	/**
	 * toHtml
	 *
	 * @return string
	 * @deprecated after 1.4.0.1
	 */
	protected function _toHtml()
	{
		//获取配置的pay mode
		$standard = Mage::getModel('opcreditcard/payment');
		$pay_mode = $standard->getConfigData('pay_mode');
		
		//是否开启内嵌支付
		if($pay_mode == 1){
			$this->setTemplate('op_creditcard/redirect.phtml');
		}else{
			$this->setTemplate('op_creditcard/jumpredirect.phtml');
		}
		
		return parent::_toHtml();
	
	}

	
	
	protected function creditCardForm()
	{
		
		$standard = Mage::getModel('opcreditcard/payment');
		
        $form = new Varien_Data_Form();
        $form->setAction($standard->getCreditCardUrl())
            ->setId('creditcard_payment_checkout')
            ->setName('creditcard_payment_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
        foreach ($standard->setOrder($this->getOrder())->getStandardCheckoutFormFields() as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }

        $formHTML = $form->toHtml();

        return $formHTML;
    }
    
    public function iframeHeight()
    {
    	//移动端则固定540px
    	if(Mage::getSingleton('checkout/session')->getData('pages') == 1){
    		return '540';
    	}
    	
    	$model        = Mage::getModel('opcreditcard/payment');
    	$iframeHeight = $model->getConfigData('iframe_height');
    
    	return $iframeHeight;
    
    }
    

}