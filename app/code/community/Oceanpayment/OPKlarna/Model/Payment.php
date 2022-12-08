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
 * @package 	Oceanpayment_Klarna
 * @copyright	Copyright (c) 2021 Oceanpayment,LLC. (http://www.oceanpayment.com)
 */
class Oceanpayment_OPKlarna_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'oceanpayment_klarna';
    protected $_formBlockType = 'opklarna/form';

    protected $_precisionCurrency = array(
    		'BIF','BYR','CLP','CVE','DJF','GNF','ISK','JPY','KMF','KRW',
    		'PYG','RWF','UGX','UYI','VND','VUV','XAF','XOF','XPF'
    );
    
    // Klarna return codes of payment
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
    public function getKlarnaUrl()
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
		return Mage::getUrl('opklarna/payment/return', array('_secure' => true,'_nosid' => true));
	}

	/**
	 *  Return URL for Klarna success response
	 *
	 *  @return	  string URL
	 */
	protected function getSuccessURL()
	{
		return Mage::getUrl('checkout/onepage/success', array('_secure' => true));
	}

    /**
     *  Return URL for Klarna failure response
     *
     *  @return	  string URL
     */
    protected function getErrorURL()
    {
        return Mage::getUrl('opklarna/payment/error', array('_secure' => true));
    }

	/**
	 *  Return URL for Klarna notify response
	 *
	 *  @return	  string URL
	 */
	protected function getNoticeURL()
	{
		return Mage::getUrl('opklarna/payment/notice', array('_secure' => true,'_nosid' => true));
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
        $block = $this->getLayout()->createBlock('opklarna/form', $name);
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
        return Mage::getUrl('opklarna/payment/redirect', array('_secure' => true));
    }

    /**
     *  Return Standard Checkout Form Fields for request to Klarna
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
		
		//支付币种
		$parameter['order_currency']	= $order->getOrderCurrencyCode();
		//金额
		$parameter['order_amount']		= $this->formatAmount($order->getGrandTotal(), $parameter['order_currency']);//sprintf('%.2f', $order->getGrandTotal());
		
		//判断是否启用3D功能
		if($this->getConfigData('secure_mode') == 1){
			//检验是否需要3D验证
			$validate_arr = $this->validate3D($parameter['order_currency'], $parameter['order_amount'], $billing, $shipping);
		}else{
			$validate_arr['terminal'] = $this->getConfigData('terminal');
			$validate_arr['securecode'] = $this->getConfigData('securecode');
		}
		
		//账户
		$parameter['account']			= $this->getConfigData('account');
		//终端号
		$parameter['terminal']			= $validate_arr['terminal'];
		//securecode
		$parameter['securecode']		= $validate_arr['securecode'];
		//支付方式
		// $parameter['methods']			= 'Klarna';
		//订单号
		$parameter['order_number']		= $order->getRealOrderId();
		//返回地址
		$parameter['backUrl']			= $this->getReturnURL();
		//服务器响应地址
		$parameter['noticeUrl']			= $this->getNoticeURL();
		//备注
		$parameter['order_notes']		= $order->getRealOrderId();
		//账单人名
		$parameter['billing_firstName']	= $this->OceanHtmlSpecialChars($billing->getFirstname());
		//账单人姓
		$parameter['billing_lastName']	= $this->OceanHtmlSpecialChars($billing->getLastname());
		//账单人email
		$parameter['billing_email']		= $this->OceanHtmlSpecialChars($order->getCustomerEmail());
		//账单人电话
		$parameter['billing_phone']		= $billing->getTelephone();
		//账单人国家
		$parameter['billing_country']	= $billing->getCountry();
		//账单人州(可不提交)
		$parameter['billing_state']		= $billing->getRegionCode();
		//账单人城市
		$parameter['billing_city']		= $billing->getCity();
		//账单人地址
		$parameter['billing_address']	= $billing->getStreet(1);
		//账单人邮编
		$parameter['billing_zip']		= $billing->getPostcode();
		//收货人地址信息
		//收货人名
		$parameter['ship_firstName']	= $shipping->getFirstname();
		//收货人姓
		$parameter['ship_lastName']		= $shipping->getLastname();
		//收货人手机
		$parameter['ship_phone']		= $shipping->getTelephone();
		//收货人国家
		$parameter['ship_country']		= $shipping->getCountry();
		//收货人州
		$parameter['ship_state']		= $shipping->getRegionCode();
		//收货人城市
		$parameter['ship_city']			= $shipping->getCity();
		//收货人地址
		$parameter['ship_addr']			= $shipping->getStreet(1);
		//收货人邮编
		$parameter['ship_zip']			= $shipping->getPostcode();
		//产品名称
		$parameter['productName']		= $productDetails['productName'];
		//产品SKU
		$parameter['productSku']		= $productDetails['productSku'];
		//产品数量
		$parameter['productNum']		= $productDetails['productNum'];
		//产品单价
		$parameter['productPrice']		= $productDetails['productPrice'];
		//网店程序类型
		$isMobile						= $this->isMobile() ? 'Mobile' : 'PC';
		$parameter['cart_info']			= 'Magento 1.x|V1.9.2|'.$isMobile;
		//接口版本
		$parameter['cart_api']			= '';
		//校验源字符串
		$signsrc						= $parameter['account'].$parameter['terminal'].$parameter['backUrl'].$parameter['order_number'].$parameter['order_currency'].$parameter['order_amount'].$parameter['billing_firstName'].$parameter['billing_lastName'].$parameter['billing_email'].$parameter['securecode'];
		//sha256加密结果
		$parameter['signValue']			= hash("sha256", $signsrc);
		//支付页面类型
		$parameter['pages']				= $this->isMobile() ? 1 : 0;
		//支付页面语言，默认英语
		$parameter['language']			= '';
		//支付页面显示商户logo
		$parameter['logoUrl']			= '';

		//$itemList 
        $parameter['itemList'] = '{
            "0":{
                "type":"1",
                "title":"'.substr($parameter["productName"], 0, 100).'",
                "sku":"'.$parameter["productSku"].'",
                "price":"'.($order->grand_total-$order->shipping_amount-$order->tax_amount).'",
                "quantity":"1",
                "total_amount":"'.($order->grand_total-$order->shipping_amount-$order->tax_amount).'",
                "taxRate":"'.round(($order->base_to_global_rate / ($order->grand_total-$order->shipping_amount-$order->tax_amount)),2).'",
                "taxPrice":"'.$order->base_to_global_rate.'",
                "image_url":"",
                "product_url":"",
                "remark":""
            },
            "1":{
                "type":"3",
                "title":"折扣",
                "sku":"'.$parameter["productSku"].'",
                "price":"0",
                "quantity":"0",
                "total_amount":"0",
                "taxRate":"0",
                "taxPrice":"0",
                "image_url":"",
                "product_url":"",
                "remark":""
            },
            "2":{
                "type":"4",
                "title":"运费",
                "sku":"'.$parameter["productSku"].'",
                "price":"'.$order->shipping_amount.'",
                "quantity":"1",
                "total_amount":"'.$order->shipping_amount.'",
                "taxRate":"1",
                "taxPrice":"'.$order->shipping_amount.'",
                "image_url":"",
                "product_url":"",
                "remark":""
            },
            "3":{
                "type":"5",
                "title":"税费",
                "sku":"'.$parameter["productSku"].'",
                "price":"'.$order->tax_amount.'",
                "quantity":"1",
                "total_amount":"'.$order->tax_amount.'",
                "taxRate":"1",
                "taxPrice":"'.$order->tax_amount.'",
                "image_url":"",
                "product_url":"",
                "remark":""
            }
        }';
		
		Mage::getSingleton('checkout/session')->setData('pages', $parameter['pages']);
		
		
		//记录发送到oceanpayment的post log
		$this->postLog($parameter);

		
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
	 * Return authorized languages by Klarna
	 *
	 * @param	none
	 * @return	array
	 */
	protected function _getAuthorizedLanguages()
	{
		$languages = array();
		
        foreach (Mage::getConfig()->getNode('global/payment/klarna_payment/languages')->asArray() as $data) 
		{
			$languages[$data['code']] = $data['name'];
		}
		
		return $languages;
	}
	
	/**
	 * Return language code to send to Klarna
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
     *  Return response for Klarna success payment
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
     *  Return response for Klarna failure payment
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
    
    	//记录发送到oceanpayment的post log
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
     * 检验是否需要3D验证
     */
    public function validate3D($order_currency, $order_amount, $billing, $shipping){
    
    	//是否需要3D验证
    	$is_3d = 0;
    	
    	//获取3D功能下各个的币种
    	$currencies_value_str = $this->getConfigData('secure_currency');
    	$currencies_value = explode(';', $currencies_value_str);
    	//获取3D功能下各个的金额
    	$amount_value_str = $this->getConfigData('secure_amount');
    	$amount_value = explode(';', $amount_value_str);
    	
    	$amountValidate = array_combine($currencies_value, $amount_value);
    	
    	if($amountValidate){
    		//判断金额是否为空
    		if(isset($amountValidate[$order_currency])){
    			//判断3D金额不为空
    			//判断订单金额是否大于3d设定值
    			if($order_amount >= $amountValidate[$order_currency]){
    				//需要3D
    				$is_3d = 1;
    			}
    		}else{
                //其他币种是否需要3D
                if($this->getConfigData('secure_other_currency') == 1){
                    //需要3D
                    $is_3d = 1;
                }

            }
    	}

    
    	//获取3D功能下国家列表
    	$countries_3d_str = $this->getConfigData('secure_country');
    	$countries_3d = explode(',', $countries_3d_str);
    	
    	
    
    	
    	//账单国
    	$billing_country = $billing->getCountry();
    	//收货国
    	$ship_country = $shipping->getCountry();
    
    
    	//判断账单国是否处于3D国家列表
    	if (in_array($billing_country , $countries_3d)){
    		$is_3d = 1;
    	}
    	//判断收货国是否处于3D国家列表
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
     * 格式化金额
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
			$productPrice[] = round($item->getPrice(), 2);
			
		}
		
		$productDetails['productName'] = implode(';', $productName);
		$productDetails['productSku'] = implode(';', $productSku);
		$productDetails['productNum'] = implode(';', $productNum);
		$productDetails['productPrice'] = implode(';', $productPrice);
		
    	return $productDetails;
    
    }
    

    
    /**
     * 检验是否移动端
     */
    function isMobile(){
    	// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    	if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
    		return true;
    	}
    	// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    	if (isset ($_SERVER['HTTP_VIA'])){
    		// 找不到为flase,否则为true
    		return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    	}
    	// 判断手机发送的客户端标志
    	if (isset ($_SERVER['HTTP_USER_AGENT'])){
    		$clientkeywords = array (
    				'nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel',
    				'lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm',
    				'operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile'
    		);
    		// 从HTTP_USER_AGENT中查找手机浏览器的关键字
    		if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
    			return true;
    		}
    	}
    	// 判断协议
    	if (isset ($_SERVER['HTTP_ACCEPT'])){
    		// 如果只支持wml并且不支持html那一定是移动设备
    		// 如果支持wml和html但是wml在html之前则是移动设备
    		if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))){
    			return true;
    		}
    	}
    	return false;
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