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
class Oceanpayment_OPPOLi_Block_Redirect extends Mage_Core_Block_Template
{
	/**
	 * Order instance
	 */
	protected $_order;
	
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
		$this->setTemplate('op_poli/redirect.phtml');

		return parent::_toHtml();
	
	}

	
	
	protected function poliForm()
	{
		//判断是何种设备
		include("MobileDetect.php");
		
		$detect = new MobileDetect();
		
		if($detect->isiOS()){
			$_SESSION['pages'] = 1;
		}elseif($detect->isMobile()){
			$_SESSION['pages'] = 1;
		}elseif($detect->isTablet()){
			$_SESSION['pages'] = 0;
		}else{
			$_SESSION['pages'] = 0;
		}
		
		$standard = Mage::getModel('oppoli/payment');

        $form = new Varien_Data_Form();
        $form->setAction($standard->getPOLiUrl())
            ->setId('poli_payment_checkout')
            ->setName('poli_payment_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
        foreach ($standard->setOrder($this->getOrder())->getStandardCheckoutFormFields() as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }

        $formHTML = $form->toHtml();

        return $formHTML;       

    }
    

  
}