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
 * @package 	Oceanpayment_TrustPay
 */
class Oceanpayment_OPTrustPay_PaymentController extends Mage_Core_Controller_Front_Action
{

	const PUSH 			= "[PUSH]";
	const BrowserReturn = "[Browser Return]";
	
	
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
		if($order_number != null){
			if ($this->_order == null) {
				$this->_order = Mage::getModel('sales/order');
				$this->_order->loadByIncrementId($order_number);
			}
		}else{
			if ($this->_order == null) {
				$session = Mage::getSingleton('checkout/session');
				$this->_order = Mage::getModel('sales/order');
				$this->_order->loadByIncrementId($session->getLastRealOrderId());
			}
		}
		
		return $this->_order;
	}

	/**
	 * When a customer chooses TrustPay on Checkout/Payment page
	 *
	 */
	public function redirectAction()
	{
		$session = Mage::getSingleton('checkout/session');
		$session->setTrustPayPaymentQuoteId($session->getQuoteId());

		$order = $this->getOrder();

		if (!$order->getId()) {
			$this->norouteAction();
			return;
		}

		$order->addStatusToHistory(
		$order->getStatus(),
		Mage::helper('optrustpay')->__('Customer was redirected to trustpay')
		);
		$order->save();
		
		$this->getResponse()
		->setBody($this->getLayout()
				->createBlock('optrustpay/redirect')
				->setOrder($order)
				->toHtml());
		
		
		
		$session->unsQuoteId();
	}

	/**
	 *  TrustPay response router
	 *
	 *  @param    none
	 *  @return	  void
	 */
	public function noticeAction()
	{
		//获取推送输入流XML
		$xml_str = file_get_contents("php://input");
		
		//判断返回的输入流是否为xml
		if($this->xml_parser($xml_str)){
			$xml = new DOMDocument();
			$xml->loadXML($xml_str);
				
			//把推送参数赋值到$_REQUEST
			$_REQUEST['response_type']	  = (string)$xml->response_type;
			$_REQUEST['account']		  = (string)$xml->account;
			$_REQUEST['terminal'] 	      = (string)$xml->terminal;
			$_REQUEST['payment_id'] 	  = (string)$xml->payment_id;
			$_REQUEST['order_number']     = (string)$xml->order_number;
			$_REQUEST['order_currency']   = (string)$xml->order_currency;
			$_REQUEST['order_amount']     = (string)$xml->order_amount;
			$_REQUEST['payment_status']   = (string)$xml->payment_status;
			$_REQUEST['payment_details']  = (string)$xml->payment_details;
			$_REQUEST['signValue'] 	      = (string)$xml->signValue;
			$_REQUEST['order_notes']	  = (string)$xml->order_notes;
			$_REQUEST['card_number']	  = (string)$xml->card_number;
			$_REQUEST['payment_authType'] = (string)$xml->payment_authType;
			$_REQUEST['payment_risk'] 	  = (string)$xml->payment_risk;
			$_REQUEST['methods'] 	  	  = (string)$xml->methods;
			$_REQUEST['payment_country']  = (string)$xml->payment_country;
			$_REQUEST['payment_solutions']= (string)$xml->payment_solutions;
		}
		
		
		$model = Mage::getModel('optrustpay/payment');
		$this->_order = Mage::getModel('sales/order');
		$order   = $this->_order->loadByIncrementId($_REQUEST['order_number']);    //载入order模块

		if($_REQUEST['response_type'] == 1){
				
			//交易推送类型
			$this->returnLog(self::PUSH);
				
			switch($this->_validated()){
				case 1:
					//支付成功
					$order->addStatusToHistory(
					$model->getConfigData('order_status_payment_accepted'),
					Mage::helper('optrustpay')->__(self::PUSH.'Payment Success!
    					      (payment_id:'.$_REQUEST['payment_id'].
		    					      ' | order_number:'.$_REQUEST['order_number'].
		    					      ' | '.$_REQUEST['order_currency'].':'.$_REQUEST['order_amount'].
		    					      ' | payment_details:'.$_REQUEST['payment_details'].')'),true
					);
					//发送邮件
					if($model->getConfigData('send_email') == 1){
						$order->sendNewOrderEmail();
					}
					//自动Invoice
					if($model->getConfigData('automatic_invoice') == 1){
						$this->saveInvoice($order);
					}
					$order->save();
					break;
				case 0:
					//支付失败
					$order->addStatusToHistory(
					$model->getConfigData('order_status_payment_refused'),
					Mage::helper('optrustpay')->__(self::PUSH.'Payment Failed!
    				         (payment_id:'.$_REQUEST['payment_id'].
		    				         ' | order_number:'.$_REQUEST['order_number'].
		    				         ' | '.$_REQUEST['order_currency'].':'.$_REQUEST['order_amount'].
		    				         ' | payment_details:'.$_REQUEST['payment_details'].')')
					);
					$order->save();
					break;
				case -1:
					//交易待处理
					$order->addStatusToHistory(
					$model->getConfigData('order_status_payment_pending'),
					Mage::helper('optrustpay')->__(self::PUSH.'Payment Pending!
    					     (payment_id:'.$_REQUEST['payment_id'].
		    					     ' | order_number:'.$_REQUEST['order_number'].
		    					     ' | '.$_REQUEST['order_currency'].':'.$_REQUEST['order_amount'].
		    					     ' | payment_details:'.$_REQUEST['payment_details'].')')
					);
					$order->save();
					break;
				case 20061:
					//订单号重复
					break;
				case 999:
					//加密值错误或系统异常
					break;
				default:
		
			}
				
			echo "receive-ok";
				
		}
		
		
	}

	
	/**
	 *  Return payment page
	 *
	 *  @param    none
	 *  @return	  void
	 */
	public function returnAction()
	{
		//输出加载动画
		$this->loadingGif();

		$model = Mage::getModel('optrustpay/payment');
		
		//推送操作
		$this->noticeAction();

		$order = $this->getOrder($_REQUEST['order_number']);      //载入order模块	
		
		switch($this->_validated()){
			case 1:
				//支付成功
				$order->addStatusToHistory(
				$model->getConfigData('order_status_payment_accepted'),
				Mage::helper('optrustpay')->__(self::BrowserReturn.'Payment succeed!
						(payment_id:'.$_REQUEST['payment_id'].
						' | order_number:'.$_REQUEST['order_number'].
						' | '.$_REQUEST['order_currency'].':'.$_REQUEST['order_amount'].
						' | payment_details:'.$_REQUEST['payment_details'].')'),true
				);
		
				//发送邮件
				if($model->getConfigData('send_email') == 2){
					$order->sendNewOrderEmail();
				}
				//自动Invoice
				if($model->getConfigData('automatic_invoice') == 2){
					$this->saveInvoice($order);
				}
				$order->save();
				$this->getJslocationreplace(Mage::getUrl('optrustpay/payment/success'));
				break;
			case 0:
				//支付失败
				$order->addStatusToHistory(
				$model->getConfigData('order_status_payment_refused'),
				Mage::helper('optrustpay')->__(self::BrowserReturn.'Payment failed!
						(payment_id:'.$_REQUEST['payment_id'].
						' | order_number:'.$_REQUEST['order_number'].
						' | '.$_REQUEST['order_currency'].':'.$_REQUEST['order_amount'].
						' | payment_details:'.$_REQUEST['payment_details'].')')
				);
				$order->save();
				$this->getJslocationreplace(Mage::getUrl('optrustpay/payment/failure'));
				break;
			case -1:
				//交易待处理
				$order->addStatusToHistory(
				$model->getConfigData('order_status_payment_pending'),
				Mage::helper('optrustpay')->__(self::BrowserReturn.'Payment Wait for the Bank processing!
						(payment_id:'.$_REQUEST['payment_id'].
						' | order_number:'.$_REQUEST['order_number'].
						' | '.$_REQUEST['order_currency'].':'.$_REQUEST['order_amount'].
						' | payment_details:'.$_REQUEST['payment_details'].')')
				);
				$order->save();
				$this->getJslocationreplace(Mage::getUrl('optrustpay/payment/pending'));
				break;
			case 20061:
				//订单号重复
				$this->getJslocationreplace(Mage::getUrl('optrustpay/payment/failure'));
			case 999:
				//加密值错误或系统异常
				$this->getJslocationreplace(Mage::getUrl('optrustpay/payment/failure'));
			default:
				$this->getJslocationreplace(Mage::getUrl('optrustpay/payment/failure'));
					
		}
			

		
	}

	private function _validated()
	{
        //载入模块
		$model            = Mage::getModel('optrustpay/payment');
		
		//载入session模块
		$session          = Mage::getSingleton('checkout/session');
		
		//获取订单
		$order            = $this->getOrder($_REQUEST['order_number']);
		
		//获取账号
		$account          = $model->getConfigData('account');
		
		//返回终端号
		$terminal         = $_REQUEST['terminal'];
		
		//返回Oceanpayment的支付唯一号
		$payment_id       = $_REQUEST['payment_id'];
		
		//返回网站订单号
		$order_number     = $_REQUEST['order_number'];
		
		//返回交易币种
		$order_currency   = $_REQUEST['order_currency'];
		
		//返回交易金额
		$order_amount     = $_REQUEST['order_amount'];//sprintf('%.2f', $order->getGrandTotal());
		
		//返回交易状态
		$payment_status   = $_REQUEST['payment_status'];
		
		//返回支付详情
		$payment_details  = $_REQUEST['payment_details'];
		
		//用于支付结果页面显示
		$_SESSION['payment_details'] = $payment_details;
		
		//用于支付结果页面显示响应代码
		$getErrorCode 	 		  	 = explode(':', $payment_details);	
		$_SESSION['errorCode']       = $getErrorCode[0];

		//返回解决办法
		$_SESSION['payment_solutions']= $_REQUEST['payment_solutions'];
		
		//获取securecode
		$securecode        = $model->getConfigData('securecode');
		
		//返回备注
		$order_notes       = $_REQUEST['order_notes'];
		
		//未通过的风控规则
		$payment_risk      = $_REQUEST['payment_risk'];
		
		//返回支付信用卡卡号
		$card_number       = $_REQUEST['card_number'];
		
		//返回交易类型
		$payment_authType  = $_REQUEST['payment_authType'];
		
		//返回数据签名
		$back_signValue    = $_REQUEST['signValue'];
		
		//SHA256加密
		$local_signValue = hash("sha256",$account.$terminal.$order_number.$order_currency.$order_amount.$order_notes.$card_number.
					$payment_id.$payment_authType.$payment_status.$payment_details.$payment_risk.$securecode);
    
		
 		//是否点击浏览器后退造成订单号重复 20061
		if($_SESSION['errorCode'] == 20061){
			return 20061;
		}

		//加密校验
		if(strtoupper($local_signValue) == strtoupper($back_signValue)){
			//支付状态
			if ($payment_status == 1) {
				return 1;
			} elseif ($payment_status == -1) {
				return -1;
			} elseif ($payment_status == 0) {
				return 0;
			} else{
				return 999;
			}
		}else{
			return 999;
		}
		
	}

	
	
	
	
	
	/**
	 * return log
	 */
	public function returnLog($logType){
	
		$filedate   = date('Y-m-d');
		$returndate = date('Y-m-d H:i:s');
		$newfile    = fopen( "oceanpayment_log/" . $filedate . ".log", "a+" );
		$return_log = $returndate . $logType . "\r\n";
		foreach ($_REQUEST as $k=>$v){
			$return_log .= $k . " = " . $v . "\r\n";
		}
		$return_log = $return_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");
		$filename   = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );
		fwrite($filename,$return_log);
		fclose($filename);
		fclose($newfile);
	
	}
	
	
	
	
	/**
	 * Get one page checkout model
	 *
	 * @return Mage_Checkout_Model_Type_Onepage
	 */
	public function getOnepage()
	{
		return Mage::getSingleton('checkout/type_onepage');
	}
	
	/**
	 *  Save invoice for order
	 *
	 *  @param    Mage_Sales_Model_Order $order
	 *  @return	  boolean Can save invoice or not
	 */
	protected function saveInvoice(Mage_Sales_Model_Order $order)
	{
		if ($order->canInvoice()) {
			$convertor = Mage::getModel('sales/convert_order');
			$invoice = $convertor->toInvoice($order);
			foreach ($order->getAllItems() as $orderItem) {
				if (!$orderItem->getQtyToInvoice()) {
					continue;
				}
				$item = $convertor->itemToInvoiceItem($orderItem);
				$item->setQty($orderItem->getQtyToInvoice());
				$invoice->addItem($item);
			}
			$invoice->collectTotals();
			$invoice->register()->capture();
			Mage::getModel('core/resource_transaction')
			->addObject($invoice)
			->addObject($invoice->getOrder())
			->save();
			return true;
		}
	
		return false;
	}
	
	
	
	/**
	 *  Success payment page
	 *
	 *  @param    none
	 *  @return	  void
	 */
	public function successAction()
	{

		$session = $this->getOnepage()->getCheckout();
		if (!$session->getLastSuccessQuoteId()) {
			$this->_redirect('checkout/cart');
			return;
		}
		
		$lastQuoteId = $session->getLastQuoteId();
		$lastOrderId = $session->getLastOrderId();
		$lastRecurringProfiles = $session->getLastRecurringProfileIds();
		if (!$lastQuoteId || (!$lastOrderId && empty($lastRecurringProfiles))) {
			$this->_redirect('checkout/cart');
			return;
		}
		
		$session->clear();
		
		$this->loadLayout();
		$this->getLayout()->getBlock('root')->setTemplate('page/2columns-right.phtml');
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('optrustpay/success'));
		$this->renderLayout();
	}
	
	/**
	 *  Failure payment page
	 *
	 *  @param    none
	 *  @return	  void
	 */
	public function failureAction()
	{
		$lastQuoteId = $this->getOnepage()->getCheckout()->getLastQuoteId();
		$lastOrderId = $this->getOnepage()->getCheckout()->getLastOrderId();
	
		if (!$lastQuoteId || !$lastOrderId) {
			$this->_redirect('checkout/cart');
			return;
		}
	
		$this->loadLayout();
		$this->getLayout()->getBlock('root')->setTemplate('page/2columns-right.phtml');
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('optrustpay/failure'));
		$this->renderLayout();
	}
	
    
    
	/**
	 *  Pending payment page
	 *
	 *  @param    none
	 *  @return	  void
	 */
	public function pendingAction()
	{
		$this->loadLayout();
		$this->getLayout()->getBlock('root')->setTemplate('page/2columns-right.phtml');
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('optrustpay/pending'));
		$this->renderLayout();
	}
	
	
	/**
	 *  Error payment page
	 *
	 *  @param    none
	 *  @return	  void
	 */
	public function errorAction()
	{
		$session = Mage::getSingleton('checkout/session');
		$errorMsg = Mage::helper('optrustpay')->__(' There was an error occurred during paying process.');
	
		$order = $this->getOrder();
	
		if (!$order->getId()) {
			$this->norouteAction();
			return;
		}
		if ($order instanceof Mage_Sales_Model_Order && $order->getId()) {
			$order->addStatusToHistory(
					Mage_Sales_Model_Order::STATE_CANCELED,//$order->getStatus(),
					Mage::helper('optrustpay')->__('Customer returned from Teleingreso.') . $errorMsg
			);
	
			$order->save();
		}
	
		$this->loadLayout();
		$this->renderLayout();
		Mage::getSingleton('checkout/session')->unsLastRealOrderId();
	}
	
	/**
	 *  JS
	 *
	 */
	public function getJslocationreplace($url)
	{
		echo '<script type="text/javascript">parent.location.replace("'.$url.'");</script>';
	
	}
	
	/**
	 *  返回增加loading动画
	 *
	 */
	public function loadingGif()
	{
		echo '<div style="position:absolute; top:100px; left:45%; z-index:3;">';
		echo '<img src="'.Mage::getDesign()->getSkinUrl('images/op_trustpay/loading.gif').'"  />';
		echo '<div style="position:fixed; top:0; left:0; z-index:2; width:100%; height:100%; background:#fff; filter:alpha(opacity=70); opacity:0.5;"></div>';
		echo '</div>';
	}
	
	
	
	/**
	 *  判断是否为xml
	 *
	 */
	function xml_parser($str){
		$xml_parser = xml_parser_create();
		if(!xml_parse($xml_parser,$str,true)){
			xml_parser_free($xml_parser);
			return false;
		}else {
			return true;
		}
	}
    
   
	
	
	
	
}


