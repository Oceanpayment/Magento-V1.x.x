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
class Oceanpayment_OPCreditCard_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'oceanpayment_creditcard';
    protected $_formBlockType = 'opcreditcard/form';

    protected $_precisionCurrency = array(
    		'BIF','BYR','CLP','CVE','DJF','GNF','ISK','JPY','KMF','KRW',
    		'PYG','RWF','UGX','UYI','VND','VUV','XAF','XOF','XPF'
    );
    
    // CreditCard return codes of payment
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
    public function getCreditCardUrl()
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
		return Mage::getUrl('opcreditcard/payment/return', array('_secure' => true,'_nosid' => true));
	}

	/**
	 *  Return URL for CreditCard success response
	 *
	 *  @return	  string URL
	 */
	protected function getSuccessURL()
	{
		return Mage::getUrl('checkout/onepage/success', array('_secure' => true));
	}

    /**
     *  Return URL for CreditCard failure response
     *
     *  @return	  string URL
     */
    protected function getErrorURL()
    {
        return Mage::getUrl('opcreditcard/payment/error', array('_secure' => true));
    }

	/**
	 *  Return URL for CreditCard notify response
	 *
	 *  @return	  string URL
	 */
	protected function getNoticeURL()
	{
		return Mage::getUrl('opcreditcard/payment/notice', array('_secure' => true,'_nosid' => true));
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
        $block = $this->getLayout()->createBlock('opcreditcard/form', $name);
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
        return Mage::getUrl('opcreditcard/payment/redirect', array('_secure' => true));
    }

    /**
     *  Return Standard Checkout Form Fields for request to CreditCard
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
		
		
		$parameter = array();
		
		//????????????
		$parameter['order_currency']	= $order->getOrderCurrencyCode();
		//??????
		$parameter['order_amount']		= $this->formatAmount($order->getGrandTotal(), $parameter['order_currency']);//sprintf('%.2f', $order->getGrandTotal());
		
		//??????????????????3D??????
		if($this->getConfigData('secure_mode') == 1){
			//??????????????????3D??????
			$validate_arr = $this->validate3D($parameter['order_currency'], $parameter['order_amount'], $billing, $shipping);
		}else{
			$validate_arr['terminal'] = $this->getConfigData('terminal');
			$validate_arr['securecode'] = $this->getConfigData('securecode');
		}
		
		//??????
		$parameter['account']			= $this->getConfigData('account');
		//?????????
		$parameter['terminal']			= $validate_arr['terminal'];
		//securecode
		$parameter['securecode']		= $validate_arr['securecode'];
		//????????????
		$parameter['methods']			= 'Credit Card';
		//?????????
		$parameter['order_number']		= $order->getRealOrderId();
		//????????????
		$parameter['backUrl']			= $this->getReturnURL();
		//?????????????????????
		$parameter['noticeUrl']			= $this->getNoticeURL();
		//??????
		$parameter['order_notes']		= $order->getRealOrderId();
		//????????????
		$parameter['billing_firstName']	= $this->OceanHtmlSpecialChars($billing->getFirstname());
		//????????????
		$parameter['billing_lastName']	= $this->OceanHtmlSpecialChars($billing->getLastname());
		//?????????email
		$parameter['billing_email']		= $this->OceanHtmlSpecialChars($order->getCustomerEmail());
		//???????????????
		$parameter['billing_phone']		= $billing->getTelephone();
		//???????????????
		$parameter['billing_country']	= $billing->getCountry();
		//????????????(????????????)
		$parameter['billing_state']		= $billing->getRegionCode();
		//???????????????
		$parameter['billing_city']		= $billing->getCity();
		//???????????????
		$parameter['billing_address']	= $billing->getStreet(1);
		//???????????????
		$parameter['billing_zip']		= $billing->getPostcode();
		//?????????????????????
		//????????????
		$parameter['ship_firstName']	= $shipping->getFirstname();
		//????????????
		$parameter['ship_lastName']		= $shipping->getLastname();
		//???????????????
		$parameter['ship_phone']		= $shipping->getTelephone();
		//???????????????
		$parameter['ship_country']		= $shipping->getCountry();
		//????????????
		$parameter['ship_state']		= $shipping->getRegionCode();
		//???????????????
		$parameter['ship_city']			= $shipping->getCity();
		//???????????????
		$parameter['ship_addr']			= $shipping->getStreet(1);
		//???????????????
		$parameter['ship_zip']			= $shipping->getPostcode();
		//????????????
		$parameter['productName']		= $productDetails['productName'];
		//??????SKU
		$parameter['productSku']		= $productDetails['productSku'];
		//????????????
		$parameter['productNum']		= $productDetails['productNum'];
		//????????????
		$parameter['productPrice']		= $productDetails['productPrice'];
		//??????????????????
		$isMobile						= $this->isMobile() ? 'Mobile' : 'PC';
		$parameter['cart_info']			= 'Magento 1.x|V1.9.2|'.$isMobile;
		//????????????
		$parameter['cart_api']			= '';
		//??????????????????
		$signsrc						= $parameter['account'].$parameter['terminal'].$parameter['backUrl'].$parameter['order_number'].$parameter['order_currency'].$parameter['order_amount'].$parameter['billing_firstName'].$parameter['billing_lastName'].$parameter['billing_email'].$parameter['securecode'];
		//sha256????????????
		$parameter['signValue']			= hash("sha256", $signsrc);
		//??????????????????
		$parameter['pages']				= $this->isMobile() ? 1 : 0;
		//?????????????????????????????????
		$parameter['language']			= '';
		//????????????????????????logo
		$parameter['logoUrl']			= '';
		
		Mage::getSingleton('checkout/session')->setData('pages', $parameter['pages']);
		
		
		//???????????????oceanpayment???post log
		$this->postLog($parameter);

		
        return $parameter;
    }

	
	//????????????????????????????????????????????????????????????
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
	//?????????????????????????????????????????????????????????????????????	
	
	/**
	 * Return authorized languages by CreditCard
	 *
	 * @param	none
	 * @return	array
	 */
	protected function _getAuthorizedLanguages()
	{
		$languages = array();
		
        foreach (Mage::getConfig()->getNode('global/payment/creditcard_payment/languages')->asArray() as $data) 
		{
			$languages[$data['code']] = $data['name'];
		}
		
		return $languages;
	}
	
	/**
	 * Return language code to send to CreditCard
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
     *  Return response for CreditCard success payment
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
     *  Return response for CreditCard failure payment
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
     * post log
     */
    public function postLog($data){
    
    	//???????????????oceanpayment???post log
    	$filedate = date('Y-m-d');
    	$newfile  = fopen( "oceanpayment_log/" . $filedate . ".log", "a+" );
    	$post_log = date('Y-m-d H:i:s')."[Sent to Oceanpayment]\r\n";
    	foreach ($data as $k=>$v){
    		$post_log .= $k . " = " . $v . "\r\n";
    	}
    	$post_log = $post_log . "*************************************\r\n";
    	$post_log = $post_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");
    	$filename = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );
    	fwrite($filename,$post_log);
    	fclose($filename);
    	fclose($newfile);
    	 
    }
    
    
    /**
     * ??????????????????3D??????
     */
    public function validate3D($order_currency, $order_amount, $billing, $shipping){
    
    	//????????????3D??????
    	$is_3d = 0;
    	
    	//??????3D????????????????????????
    	$currencies_value_str = $this->getConfigData('secure_currency');
    	$currencies_value = explode(';', $currencies_value_str);
    	//??????3D????????????????????????
    	$amount_value_str = $this->getConfigData('secure_amount');
    	$amount_value = explode(';', $amount_value_str);
    	
    	$amountValidate = array_combine($currencies_value, $amount_value);
    	
    	if($amountValidate){
    		//????????????????????????
    		if(isset($amountValidate[$order_currency])){
    			//??????3D???????????????
    			//??????????????????????????????3d?????????
    			if($order_amount >= $amountValidate[$order_currency]){
    				//??????3D
    				$is_3d = 1;
    			}
    		}else{
                //????????????????????????3D
                if($this->getConfigData('secure_other_currency') == 1){
                    //??????3D
                    $is_3d = 1;
                }

            }
    	}

    
    	//??????3D?????????????????????
    	$countries_3d_str = $this->getConfigData('secure_country');
    	$countries_3d = explode(',', $countries_3d_str);
    	
    	
    
    	
    	//?????????
    	$billing_country = $billing->getCountry();
    	//?????????
    	$ship_country = $shipping->getCountry();
    
    
    	//???????????????????????????3D????????????
    	if (in_array($billing_country , $countries_3d)){
    		$is_3d = 1;
    	}
    	//???????????????????????????3D????????????
    	if (in_array($ship_country , $countries_3d)){
    		$is_3d = 1;
    	}
    
    
    
    	if($is_3d ==  0){
    		$validate_arr['terminal'] = $this->getConfigData('terminal');
    		$validate_arr['securecode'] = $this->getConfigData('securecode');
    	}elseif($is_3d == 1){
    		//3D
    		$validate_arr['terminal'] = $this->getConfigData('secure_terminal');
    		$validate_arr['securecode'] = $this->getConfigData('secure_securecode');
    		Mage::getSingleton('checkout/session')->setData('is_3d', 1);
    	}
    
    
    	return $validate_arr;
    
    }
    
    /**
     * ???????????????
     */
    function formatAmount($order_amount, $order_currency){
    
    	if(in_array($order_currency, $this->_precisionCurrency)){
    		$order_amount = round($order_amount, 0);
    	}else{
    		$order_amount = round($order_amount, 2);
    	}
    
    	return $order_amount;
    
    }

    /**
     * ??????????????????
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
			$productPrice[] = round($item->getPrice(), 2);
			
		}
		
		$productDetails['productName'] = implode(';', $productName);
		$productDetails['productSku'] = implode(';', $productSku);
		$productDetails['productNum'] = implode(';', $productNum);
		$productDetails['productPrice'] = implode(';', $productPrice);
		
    	return $productDetails;
    
    }
    

    
    /**
     * ?????????????????????
     */
    function isMobile(){
    	// ?????????HTTP_X_WAP_PROFILE????????????????????????
    	if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
    		return true;
    	}
    	// ??????via????????????wap????????????????????????,?????????????????????????????????
    	if (isset ($_SERVER['HTTP_VIA'])){
    		// ????????????flase,?????????true
    		return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    	}
    	// ????????????????????????????????????
    	if (isset ($_SERVER['HTTP_USER_AGENT'])){
    		$clientkeywords = array (
    				'nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel',
    				'lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm',
    				'operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile'
    		);
    		// ???HTTP_USER_AGENT????????????????????????????????????
    		if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
    			return true;
    		}
    	}
    	// ????????????
    	if (isset ($_SERVER['HTTP_ACCEPT'])){
    		// ???????????????wml???????????????html????????????????????????
    		// ????????????wml???html??????wml???html????????????????????????
    		if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))){
    			return true;
    		}
    	}
    	return false;
    }
    
    
	/**
	 * ????????????Html??????????????????
	 */
	function OceanHtmlSpecialChars($parameter){

		//??????????????????
		$parameter = trim($parameter);

		//??????"?????????,<?????????,>?????????,'?????????
		$parameter = str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),$parameter);
		
		return $parameter;

	}

}