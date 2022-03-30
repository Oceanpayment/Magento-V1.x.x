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
 * @package 	Oceanpayment_THCash
 */
class Oceanpayment_OPTHCash_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'oceanpayment_thcash';
    protected $_formBlockType = 'opthcash/form';

    // THCash return codes of payment
    const RETURN_CODE_ACCEPTED      = 'Success';
    const RETURN_CODE_TEST_ACCEPTED = 'Success';
    const RETURN_CODE_ERROR         = 'Fail';

    // Payment configuration
    protected $_isGateway               = false;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    

    // Order instance
    protected $_order = null;

    /**
     *  Returns Target URL
     *
     *  @return	  string Target URL
     */
    public function getTHCashUrl()
    {
        $url = $this->getConfigData('transport_url');
        return $url;
    }

    /**
     *  Return back URL
     *
     *  @return	  string URL
     */
	protected function getReturnURL()
	{
		return Mage::getUrl('opthcash/payment/return', array('_secure' => true,'_nosid' => true));
	}

	/**
	 *  Return URL for THCash success response
	 *
	 *  @return	  string URL
	 */
	protected function getSuccessURL()
	{
		return Mage::getUrl('checkout/onepage/success', array('_secure' => true));
	}

    /**
     *  Return URL for THCash failure response
     *
     *  @return	  string URL
     */
    protected function getErrorURL()
    {
        return Mage::getUrl('opthcash/payment/error', array('_secure' => true));
    }

	/**
	 *  Return URL for THCash notify response
	 *
	 *  @return	  string URL
	 */
	protected function getNoticeURL()
	{
		return Mage::getUrl('opthcash/payment/notice', array('_secure' => true,'_nosid' => true));
	}

    /**
     * Capture payment
     *
     * @param   Varien_Object $orderPayment
     * @return  Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)
            ->setLastTransId($this->getTransactionId());

        return $this;
    }

    /**
     *  Form block description
     *
     *  @return	 object
     */
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('opthcash/form_payment', $name);
        $block->setMethod($this->_code);
        $block->setPayment($this->getPayment());

        return $block;
    }

    /**
     *  Return Order Place Redirect URL
     *
     *  @return	  string Order Redirect URL
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('opthcash/payment/redirect', array('_secure' => true));
    }

    /**
     *  Return Standard Checkout Form Fields for request to THCash
     *
     *  @return	  array Array of hidden form fields
     */
    public function getStandardCheckoutFormFields()
    {
        $session = Mage::getSingleton('checkout/session');
        
        $order = $this->getOrder();
        if (!($order instanceof Mage_Sales_Model_Order)) {
            Mage::throwException($this->_getHelper()->__('Cannot retrieve order object'));
        }
		$billing = $order->getBillingAddress();
		$shipping = $order->getShippingAddress();
		$productDetails = $this->getProductItems($order->getAllItems());
		
		
		//账户
		$account           = $this->getConfigData('account');
		//终端号
		$terminal          = $this->getConfigData('terminal');
		//securecode
		$securecode        = $this->getConfigData('securecode');
		//支付方式
		$methods           = 'THA_Direct_Cash';
		//订单号
		$order_number      = $order->getRealOrderId();
		//支付币种
		$order_currency    = $order->getOrderCurrencyCode();
		//金额
		$order_amount      = sprintf('%.2f', $order->getGrandTotal());
		//返回地址
		$backUrl           = $this->getReturnURL();
		//服务器响应地址
		$noticeUrl         = $this->getNoticeURL();
		//备注
		$order_notes       = $order->getRealOrderId();
		//账单人名
		$billing_firstName = $this->OceanHtmlSpecialChars($billing->getFirstname());
		//账单人姓
		$billing_lastName  = $this->OceanHtmlSpecialChars($billing->getLastname());
		//账单人email
		$billing_email     = $this->OceanHtmlSpecialChars($order->getCustomerEmail());
		//账单人电话
		$billing_phone     = $billing->getTelephone();
		//账单人国家
		$billing_country   = $billing->getCountry();
		//账单人州(可不提交)
		$billing_state     = $billing->getRegionCode();
		//账单人城市
		$billing_city      = $billing->getCity();
		//账单人地址
		$billing_address   = $billing->getStreet(1);
		//账单人邮编
		$billing_zip       = $billing->getPostcode();		
		//收货人地址信息
		//收货人名
		$ship_firstName    = $shipping->getFirstname();
		//收货人姓
		$ship_lastName	   = $shipping->getLastname();
		//收货人手机
		$ship_phone 	   = $shipping->getTelephone();
		//收货人国家
		$ship_country 	   = $shipping->getCountry();
		//收货人州
		$ship_state   	   = $shipping->getRegionCode();
		//收货人城市
		$ship_city   	   = $shipping->getCity();
		//收货人地址
		$ship_addr		   = $shipping->getStreet(1);
		//收货人邮编
		$ship_zip 		   = $shipping->getPostcode();
		//产品名称
		$productName	   = $productDetails['productName'];
		//产品SKU
		$productSku		   = $productDetails['productSku'];
		//产品数量
		$productNum		   = $productDetails['productNum'];
		//网店程序类型
		$cart_info         = 'magento';
		//接口版本
		$cart_api          = 'V1.7.1';
		//校验源字符串
		$signsrc           = $account.$terminal.$backUrl.$order_number.$order_currency.$order_amount.$billing_firstName.$billing_lastName.$billing_email.$securecode;
		//sha256加密结果
		$signValue         = hash("sha256",$signsrc);
		//银行代码
		$pay_bankCode      = 'OMISE_TL';
		
		//记录发送到oceanpayment的post log
	    $filedate = date('Y-m-d');  
	    $postdate = date('Y-m-d H:i:s');    
	    $newfile  = fopen( "oceanpayment_log/" . $filedate . ".log", "a+" );    
	    $post_log = $postdate."[POST to Oceanpayment]\r\n" . 
	 	            "account = "           .$account . "\r\n".
	                "terminal = "          .$terminal . "\r\n".
         	        "backUrl = "           .$backUrl . "\r\n".
         	        "noticeUrl = "         .$noticeUrl . "\r\n".
         	        "order_number = "      .$order_number . "\r\n".
         	        "order_currency = "    .$order_currency . "\r\n".
         	        "order_amount = "      .$order_amount . "\r\n".
         	        "methods = "           .$methods . "\r\n".
         	        "signValue = "         .$signValue . "\r\n".
         	        "billing_firstName = " .$billing_firstName . "\r\n".
         	        "billing_lastName = "  .$billing_lastName . "\r\n".
         	        "billing_email = "     .$billing_email . "\r\n".
         	        "billing_phone = "     .$billing_phone . "\r\n".
         	        "billing_country = "   .$billing_country . "\r\n".
         	        "billing_state = "     .$billing_state . "\r\n".
         	        "billing_city = "      .$billing_city . "\r\n".
         	        "billing_address = "   .$billing_address . "\r\n".
         	        "billing_zip = "       .$billing_zip . "\r\n".
         	        "ship_firstName = "    .$ship_firstName . "\r\n".
         	        "ship_lastName = "     .$ship_lastName . "\r\n".
         	        "ship_phone = "        .$ship_phone . "\r\n".
         	        "ship_country = "      .$ship_country . "\r\n".
         	        "ship_state = "        .$ship_state . "\r\n".
         	        "ship_city = "     	   .$ship_city . "\r\n".
         	        "ship_addr = "   	   .$ship_addr . "\r\n".
         	        "ship_zip = "     	   .$ship_zip . "\r\n".
         	        "productName = "       .$productName . "\r\n".
         	        "productSku = "        .$productSku . "\r\n".
         	        "productNum = "        .$productNum . "\r\n".
         	        "cart_info = "         .$cart_info . "\r\n".
					"cart_api = "          .$cart_api . "\r\n".
					"order_notes = "       .$order_notes . "\r\n";  
	    $post_log = $post_log . "*************************************\r\n";    
	    $post_log = $post_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");    
	    $filename = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );   
	    fwrite($filename,$post_log);    
	    fclose($filename);   
	    fclose($newfile);


	
		
		$parameter = array('account'=>$account,
			'terminal'=>$terminal,
			'order_number'=>$order_number,
			'order_currency'=>$order_currency,
			'order_amount'=>$order_amount,
			'backUrl'=>$backUrl,
			'noticeUrl'=>$noticeUrl,
			'order_notes'=>$order_notes,
			'methods'=>$methods,
			'signValue'=>$signValue,
			'billing_firstName'=>$billing_firstName,
			'billing_lastName'=>$billing_lastName,
			'billing_email'=>$billing_email,
			'billing_phone'=>$billing_phone,
			'billing_country'=>$billing_country,
			'billing_state'=>$billing_state,
			'billing_city'=>$billing_city,
			'billing_address'=>$billing_address,
			'billing_zip'=>$billing_zip,
			'ship_firstName'=>$ship_firstName,
			'ship_lastName'=>$ship_lastName,
			'ship_phone'=>$ship_phone,
			'ship_country'=>$ship_country,
			'ship_state'=>$ship_state,
			'ship_city'=>$ship_city,
			'ship_addr'=>$ship_addr,
			'ship_zip'=>$ship_zip,
			'productName'=>$productName,
			'productSku'=>$productSku,
			'productNum'=>$productNum,
			'cart_info'=>$cart_info,
			'cart_api'=>$cart_api,
		    'pay_bankCode'=>$pay_bankCode,
		);
	
		
        return $parameter;
    }

	
	//功能函数。将变量值不为空的参数组成字符串
	public function appendParam($returnStr,$paramId,$paramValue){

		if($returnStr!=""){
			
				if($paramValue!=""){
					
					$returnStr.="&".$paramId."=".$paramValue;
				}
			
		}else{
		
			If($paramValue!=""){
				$returnStr=$paramId."=".$paramValue;
			}
		}
		
		return $returnStr;
	}
	//功能函数。将变量值不为空的参数组成字符串。结束	
	
	/**
	 * Return authorized languages by THCash
	 *
	 * @param	none
	 * @return	array
	 */
	protected function _getAuthorizedLanguages()
	{
		$languages = array();
		
        foreach (Mage::getConfig()->getNode('global/payment/thcash_payment/languages')->asArray() as $data) 
		{
			$languages[$data['code']] = $data['name'];
		}
		
		return $languages;
	}
	
	/**
	 * Return language code to send to THCash
	 *
	 * @param	none
	 * @return	String
	 */
	protected function _getLanguageCode()
	{
		// Store language
		$language = strtoupper(substr(Mage::getStoreConfig('general/locale/code'), 0, 2));

		// Authorized Languages
		$authorized_languages = $this->_getAuthorizedLanguages();

		if (count($authorized_languages) === 1) 
		{
			$codes = array_keys($authorized_languages);
			return $codes[0];
		}
		
		if (array_key_exists($language, $authorized_languages)) 
		{
			return $language;
		}
		
		// By default we use language selected in store admin
		return $this->getConfigData('language');
	}



    /**
     *  Output failure response and stop the script
     *
     *  @param    none
     *  @return	  void
     */
    public function generateErrorResponse()
    {
        die($this->getErrorResponse());
    }

    /**
     *  Return response for THCash success payment
     *
     *  @param    none
     *  @return	  string Success response string
     */
    public function getSuccessResponse()
    {
        $response = array(
            'Pragma: no-cache',
            'Content-type : text/plain',
            'Version: 1',
            'OK'
        );
        return implode("\n", $response) . "\n";
    }

    /**
     *  Return response for THCash failure payment
     *
     *  @param    none
     *  @return	  string Failure response string
     */
    public function getErrorResponse()
    {
        $response = array(
            'Pragma: no-cache',
            'Content-type : text/plain',
            'Version: 1',
            'Document falsifie'
        );
        return implode("\n", $response) . "\n";
    }

    
    /**
     * 获取订单详情
     */
    function getProductItems($AllItems){
    
    	$productDetails = array();
    	$productName = array();
    	$productSku = array();
    	$productNum = array();
    	
		foreach ($AllItems as $item) {
			$productName[] = $item->getName();
			$productSku[] = $item->getSku();
			$productNum[] = number_format($item->getQtyOrdered());
		}
		
		$productDetails['productName'] = implode(';', $productName);
		$productDetails['productSku'] = implode(';', $productSku);
		$productDetails['productNum'] = implode(';', $productNum);
    
		
    	return $productDetails;
    
    }
    

    
	/**
	 * 钱海支付Html特殊字符转义
	 */
	function OceanHtmlSpecialChars($parameter){

		//去除前后空格
		$parameter = trim($parameter);

		//转义"双引号,<小于号,>大于号,'单引号
		$parameter = str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),$parameter);
		
		return $parameter;

	}

}