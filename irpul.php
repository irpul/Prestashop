<?php 
/*
* iPresta.ir
*
* Do not edit or remove author copyright
* if you have any problem contact us at iPresta.ir
*
*  @author Danoosh Miralayi - iPresta.ir
*  @copyright  2014-2015 iPresta.ir
*  نکته مهم:
*  حذف یا تغییر این اطلاعات به هر شکلی ممنوع بوده و پیگرد قانونی دارد
*/

class irpul extends PaymentModule
{  
	private $_html = '';

	private $_go_url = 'http://irpul.ir/';

	public function __construct(){  
		$this->name = 'irpul';
		$this->tab = 'payments_gateways';
		$this->version = '1.0';
		$this->bootstrap = true;
		$this->author = 'iPresta.ir';

		$this->currencies = true;
  		$this->currencies_mode = 'checkbox';

		parent::__construct();
		$this->context = Context::getContext();
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('سامانه ایرپول');  
		$this->description = $this->l('A free module to pay online.');  
		$this->confirmUninstall = $this->l('Are you sure, you want to delete your details?');
		if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module');
		$config = Configuration::getMultiple(array('IPRESTA_irpul_UserName', 'IPRESTA_irpul_UserPassword'));			
		if (!isset($config['IPRESTA_irpul_UserName']))
			$this->warning = $this->l('Your irpul username must be configured in order to use this module');

	}  
	public function install(){
		if (!parent::install()
	    	OR !Configuration::updateValue('IPRESTA_irpul_USER', '')
			OR !Configuration::updateValue('IPRESTA_irpul_TEST', 0)
            OR !Configuration::updateValue('IPRESTA_irpul_DEBUG', 0)
	      	OR !$this->registerHook('payment')
	      	OR !$this->registerHook('paymentReturn')){
			    return false;
		}else{
		    return true;
		}
	}
	public function uninstall(){
		if (!Configuration::deleteByName('IPRESTA_irpul_USER') 
            OR !Configuration::deleteByName('IPRESTA_irpul_TEST')
			OR !Configuration::deleteByName('IPRESTA_irpul_DEBUG')
			OR !parent::uninstall())
			return false;
		return true;
	}
	
	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Merchant Code'),
						'name' => 'IPRESTA_irpul_USER',
						'class' => 'fixed-width-lg',
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Enable Debug Mode'),
						'name' => 'IPRESTA_irpul_DEBUG',
						'class' => 'fixed-width-xs',
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Yes')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('No')
							)
						),
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);
		
		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table =  $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitirpul';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}
	
	public function getConfigFieldsValues()
	{
		return array(
			'IPRESTA_irpul_USER' => Tools::getValue('IPRESTA_irpul_USER', Configuration::get('IPRESTA_irpul_USER')),
			'IPRESTA_irpul_DEBUG' => Tools::getValue('IPRESTA_irpul_DEBUG', (bool)Configuration::get('IPRESTA_irpul_DEBUG')),
		);
	}

    public function getContent()
	{
		$output = '';
		$errors = array();
		if (isset($_POST['submitirpul']))
		{
			if (empty($_POST['IPRESTA_irpul_USER']))
				$errors[] = $this->l('Your merchant code is required.');

			if (!count($errors))
			{
				Configuration::updateValue('IPRESTA_irpul_USER', $_POST['IPRESTA_irpul_USER']);
				Configuration::updateValue('IPRESTA_irpul_DEBUG', $_POST['IPRESTA_irpul_DEBUG']);
				$output = $this->displayConfirmation($this->l('Your settings have been updated.'));
			}
			else
				$output = $this->displayError(implode('<br />', $errors));
		}
		return $output.$this->renderForm();
	}
	
	public function prePayment(){
		$purchase_currency 	= new Currency(Currency::getIdByIsoCode('IRR'));
		$current_currency 	= new Currency($this->context->cookie->id_currency);			
		if($current_currency->id == $purchase_currency->id){
			$amount = number_format($this->context->cart->getOrderTotal(true, 3), 0, '', '');
		}else{
			 $amount = number_format($this->convertPriceFull($this->context->cart->getOrderTotal(true, 3), $current_currency, $purchase_currency), 0, '', '');
		}

        $token	= Configuration::get('IPRESTA_irpul_USER');
        $callback 	= $this->context->link->getModuleLink('irpul', 'validation');
        $order_id 	= substr($this->context->cart->id.rand(),-8);
		
		$email 			=  $this->context->customer->email;
		$payer_name 	= $this->context->customer->firstname .' '. $this->context->customer->lastname;
		$birthday 		= $this->context->customer->birthday;
		
		//$orderId =Tools::getValue('id_order');
		//$order = new Order($orderId);
		
		$address_id 	= $this->context->cart->id_address_delivery;
		$address 		= new Address($address_id);
		$alias 			= $address->alias;
		$city 			= $address->city;
		$address1 		= $address->address1;
		$address2 		= $address->address2;
		$postcode 		= $address->postcode;
		$phone 			= $address->phone;
		$phone_mobile 	= $address->phone_mobile;
		$customer_id 	= $address->id_customer;
		$other 			= $address->other;
		$description 	= 'تاریخ تولد: ' . $birthday . ' | شناسه کاربر:' . $customer_id . ' | سایر:' . $other;
		$customer_address = $alias . ' :' . $city . ' ' . $address1 . ' - آدرس دوم ' . $address2 . ' کد پستی:' . $postcode;

		$products_array = $this->context->cart->getProducts(true);
		$products_name 	= '';
		$i 				= 0;
		$count 			= count($products_array);	
		foreach ( $products_array as $product) {
			$products_name .= 'تعداد '. $product['cart_quantity'] . ' عدد ' . $product['name'];
			if ($i!=$count-1) {	
				$products_name .= ' | ';
			}
			$i++;
		}

		$amount = (int)$amount;
		$parameters = array(
			'method' 		=> 'payment',
			//'webgate_id' 	=> $webgate_id,
			'amount' 		=> $amount,
			'callback_url' 	=> $callback, 
			'plugin' 		=> 'prestashop',
			'order_id'		=> $order_id,
			'product'		=> $products_name,
			'payer_name'	=> $payer_name,
			'phone' 		=> $phone,
			'mobile' 		=> $phone_mobile,
			'email' 		=> $email,
			'address' 		=> $customer_address,
			'description' 	=> $description,
		);
		
		$result = post_data('https://irpul.ir/ws.php', $parameters, $token );

		if( isset($result['http_code']) ){
			$data =  json_decode($result['data'],true);

			if( isset($data['code']) && $data['code'] === 1){
				 $this->context->cookie->__set("RefId", $order_id);
				$this->context->cookie->__set("amount", (int)$amount);

				//'redirect_link' => $this->_go_url,
				$this->context->smarty->assign(array(
					'redirect_link' => $result['url'],
					'tid' 			=> $result['tran_id']
				));
				return true;
			}
			else{
				$error_message = 'Error Code: ' . $data['code'] . "<br/>" . $data['status'];
				//$this->context->controller->errors[] = $this->showMessages($result['res_code']);
				$this->context->controller->errors[] = $error_message;
				return false;
			}
		}else{
			$this->context->controller->errors[] = $this->showMessages($result['res_code']);
            return false;
		}
	}
	
	public function showMessages($result){                
		$err = 'Error!';
		if($result=='-1'){
			$err = $this->l('شناسه درگاه مشخص نشده است');
		}
		elseif($result=='-2'){
			$err = $this->l('شناسه درگاه صحیح نمی باشد');
		}
		elseif($result=='-3'){
			$err = $this->l('شما حساب کاربری خود را در ایرپول تایید نکرده اید');
		}
		elseif($result=='-4'){
			$err = $this->l('مبلغ قابل پرداخت تعیین نشده است');
		}
		elseif($result=='-5'){
			$err = $this->l('مبلغ قابل پرداخت صحیح نمی باشد');
		}
		elseif($result=='-6'){
			$err = $this->l('شناسه تراکنش صحیح نمی باشد');
		}
		elseif($result=='-7'){
			$err = $this->l('آدرس بازگشت مشخص نشده است');
		}
		elseif($result=='-8'){
			$err = $this->l('آدرس بازگشت صحیح نمی باشد');
		}
		elseif($result=='-9'){
			$err = $this->l('آدرس ایمیل وارد شده صحیح نمی باشد');
		}
		elseif($result=='-10'){
			$err = $this->l('شماره تلفن وارد شده صحیح نمی باشد');
		}
		elseif($result=='-12'){
			$err = $this->l('نام پلاگین (Plugin) مشخص نشده است');
		}
		elseif($result=='-13'){
			$err = $this->l('نام پلاگین (Plugin) صحیح نیست');
		}
		else{
			$err = $this->l('پاسخی دریافت نشد لطفا مجدد تلاش کنید');
		}
		return $err;
	}
	
	public function post_data($url,$params,$token) {
		ini_set('default_socket_timeout', 15);

		$headers = array(
			"Authorization: token= {$token}",
			'Content-type: application/json'
		);

		$handle = curl_init($url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($handle, CURLOPT_TIMEOUT, 40);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($params) );
		curl_setopt($handle, CURLOPT_HTTPHEADER, $headers );

		$response = curl_exec($handle);
		//error_log('curl response1 : '. print_r($response,true));

		$msg='';
		$http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));

		$status= true;

		if ($response === false) {
			$curl_errno = curl_errno($handle);
			$curl_error = curl_error($handle);
			$msg .= "Curl error $curl_errno: $curl_error";
			$status = false;
		}

		curl_close($handle);//dont move uppder than curl_errno

		if( $http_code == 200 ){
			$msg .= "Request was successfull";
		}
		else{
			$status = false;
			if ($http_code == 400) {
				$status = true;
			}
			elseif ($http_code == 401) {
				$msg .= "Invalid access token provided";
			}
			elseif ($http_code == 502) {
				$msg .= "Bad Gateway";
			}
			elseif ($http_code >= 500) {// do not wat to DDOS server if something goes wrong
				sleep(2);
			}
		}

		$res['http_code'] 	= $http_code;
		$res['status'] 		= $status;
		$res['msg'] 		= $msg;
		$res['data'] 		= $response;

		if(!$status){
			//error_log(print_r($res,true));
		}
		return $res;
	}


	public function verify($tran_id){
        $token = Configuration::get('IPRESTA_irpul_USER');
		
        $purchase_currency = new Currency(Currency::getIdByIsoCode('IRR'));
        $current_currency = new Currency($this->context->cookie->id_currency);
		$res = false;
		
		if($current_currency->id == $purchase_currency->id){
			$amount = number_format($this->context->cart->getOrderTotal(true, 3), 0, '', '');
		}else{
			$amount = number_format($this->convertPriceFull($this->context->cart->getOrderTotal(true, 3), $current_currency, $purchase_currency), 0, '', '');
		}
		$amount = (int)$amount;
		
		$parameters = array(
			'method' 	    => 'verify',
			'trans_id' 		=> $tran_id,
			'amount'	 	=> $amount,
		);
		
		$result =  post_data('https://irpul.ir/ws.php', $parameters, $token );
		
		if( isset($result['http_code']) ){
			$data =  json_decode($result['data'],true);

			if( isset($data['code']) && $data['code'] === 1){
				$irpul_amount  = $data['amount'];
				
				if($amount == $irpul_amount){
					$res = true;
				}
				else{
					$this->context->controller->errors[] = 'مبلغ تراکنش در ایرپول (' . number_format($irpul_amount) . ' تومان) تومان با مبلغ تراکنش در سیمانت (' . number_format($amount) . ' تومان) برابر نیست';
				}
			}
			else{
				$this->context->controller->errors[] = 'خطا در پرداخت. کد خطا: ' . $data['code'] . '<br/> ' . $data['status'];
			}
		}else{
			$this->context->controller->errors[] = "عدم دریافت پاسخ";
		}

		return $res;
	}

	public function hookPayment($params){
		if (!$this->active)
			return ;
		return $this->display(__FILE__, 'payment.tpl');
	}

    public function hookPaymentReturn($params)
    {
        if (!$this->active)
            return ;

        $order = new Order(Tools::getValue('id_order'));

        $this->context->smarty->assign(array(
            'id_order' => Tools::getValue('id_order'),
			'reference' => $order->reference,
			'tran_id' => Tools::getValue('tran_id'),
            'order_id' => Tools::getValue('order_id'),
            'ver' => $this->version,

        ));

        return $this->display(__FILE__, 'confirmation.tpl');
    }

	/**
	 *
	 * @return float converted amount from a currency to an other currency
	 * @param float $amount
	 * @param Currency $currency_from if null we used the default currency
	 * @param Currency $currency_to if null we used the default currency
	 */
	public static function convertPriceFull($amount, Currency $currency_from = null, Currency $currency_to = null)
	{
		if ($currency_from === $currency_to)
			return $amount;
		if ($currency_from === null)
			$currency_from = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
		if ($currency_to === null)
			$currency_to = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
		if ($currency_from->id == Configuration::get('PS_CURRENCY_DEFAULT'))
			$amount *= $currency_to->conversion_rate;
		else
		{
            $conversion_rate = ($currency_from->conversion_rate == 0 ? 1 : $currency_from->conversion_rate);
			// Convert amount to default currency (using the old currency rate)
			$amount = Tools::ps_round($amount / $conversion_rate, 2);
			// Convert to new currency
			$amount *= $currency_to->conversion_rate;
		}
		return Tools::ps_round($amount, 2);
	}
}