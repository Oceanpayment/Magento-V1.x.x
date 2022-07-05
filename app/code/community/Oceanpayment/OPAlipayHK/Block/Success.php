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

class Oceanpayment_OPAlipayHK_Block_Success extends Mage_Core_Block_Template
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
			
		$this->setTemplate('op_alipayhk/success.phtml');
	
		return parent::_toHtml();
	}
	
	/**
	 *  
	 *
	 *  @return	  string
	 */
	public function getLocation()
	{
	    //获取配置		   
	    $standard = Mage::getModel('opalipayhk/payment');
	    $locations = $standard->getConfigData('locations');
	   
	    //是否开启
	    if($locations == 1){
	        $locations_text = $standard->getConfigData('locations_text');
	        return 'Outlet Location:'.$locations_text;
	    }else{
	    //关闭
	    }

	}
	
	public function getEntity()
	{
	
	    $standard = Mage::getModel('opalipayhk/payment');
	    $entity = $standard->getConfigData('entity');
	    //是否开启
    	if($entity == 1){
    	        $entity_text = $standard->getConfigData('entity_text');
    	        return 'Entity:'.$entity_text;
    	}else{
    	//关闭
    	}
	    
	}
	
	/**
	 * Retrieve identifier of created order
	 *
	 * @return string
	 * @deprecated after 1.4.0.1
	 */
	public function getOrderId()
	{
		return $this->_getData('order_id');
	}



	/**
	 * Check order print availability
	 *
	 * @return bool
	 * @deprecated after 1.4.0.1
	 */
	public function canPrint()
	{
		return $this->_getData('can_view_order');
	}

	/**
	 * Get url for order detale print
	 *
	 * @return string
	 * @deprecated after 1.4.0.1
	 */
	public function getPrintUrl()
	{
		return $this->_getData('print_url');
	}

	/**
	 * Get url for view order details
	 *
	 * @return string
	 * @deprecated after 1.4.0.1
	 */
	public function getViewOrderUrl()
	{
		return $this->_getData('view_order_id');
	}

	/**
	 * See if the order has state, visible on frontend
	 *
	 * @return bool
	 */
	public function isOrderVisible()
	{
		return (bool)$this->_getData('is_order_visible');
	}

	/**
	 * Getter for recurring profile view page
	 *
	 * @param $profile
	 */
	public function getProfileUrl(Varien_Object $profile)
	{
		return $this->getUrl('sales/recurring_profile/view', array('profile' => $profile->getId()));
	}

	/**
	 * Initialize data and prepare it for output
	 */
	protected function _beforeToHtml()
	{
		$this->_prepareLastOrder();
		$this->_prepareLastBillingAgreement();
		$this->_prepareLastRecurringProfiles();
		return parent::_beforeToHtml();
	}

	/**
	 * Get last order ID from session, fetch it and check whether it can be viewed, printed etc
	 */
	protected function _prepareLastOrder()
	{
		$orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
		if ($orderId) {
			$order = Mage::getModel('sales/order')->load($orderId);
			if ($order->getId()) {
				$isVisible = !in_array($order->getState(),
						Mage::getSingleton('sales/order_config')->getInvisibleOnFrontStates());
				$this->addData(array(
						'is_order_visible' => $isVisible,
						'view_order_id' => $this->getUrl('sales/order/view/', array('order_id' => $orderId)),
						'print_url' => $this->getUrl('sales/order/print', array('order_id'=> $orderId)),
						'can_print_order' => $isVisible,
						'can_view_order'  => Mage::getSingleton('customer/session')->isLoggedIn() && $isVisible,
						'order_id'  => $order->getIncrementId(),
				));
			}
		}
	}

	/**
	 * Prepare billing agreement data from an identifier in the session
	 */
	protected function _prepareLastBillingAgreement()
	{
		$agreementId = Mage::getSingleton('checkout/session')->getLastBillingAgreementId();
		$customerId = Mage::getSingleton('customer/session')->getCustomerId();
		if ($agreementId && $customerId) {
			$agreement = Mage::getModel('sales/billing_agreement')->load($agreementId);
			if ($agreement->getId() && $customerId == $agreement->getCustomerId()) {
				$this->addData(array(
						'agreement_ref_id' => $agreement->getReferenceId(),
						'agreement_url' => $this->getUrl('sales/billing_agreement/view',
								array('agreement' => $agreementId)
						),
				));
			}
		}
	}

	/**
	 * Prepare recurring payment profiles from the session
	 */
	protected function _prepareLastRecurringProfiles()
	{
		$profileIds = Mage::getSingleton('checkout/session')->getLastRecurringProfileIds();
		if ($profileIds && is_array($profileIds)) {
			$collection = Mage::getModel('sales/recurring_profile')->getCollection()
			->addFieldToFilter('profile_id', array('in' => $profileIds))
			;
			$profiles = array();
			foreach ($collection as $profile) {
				$profiles[] = $profile;
			}
			if ($profiles) {
				$this->setRecurringProfiles($profiles);
				if (Mage::getSingleton('customer/session')->isLoggedIn()) {
					$this->setCanViewProfiles(true);
				}
			}
		}
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
