<?php
/*
* iPresta.ir
*
*
*  @author iPresta.ir - Danoosh Miralayi
*  @copyright  2014-2015 iPresta.ir
*/
class irpulValidationModuleFrontController extends ModuleFrontController{
    private $order_id = '';
    private $tran_id = '';
    private $state = '';
	private $irpul_token = '';
	private $amount = '';
	private $refcode = '';

	public function __construct(){
		//$this->auth = true;
		parent::__construct();
		$this->context = Context::getContext();
		$this->ssl = true;
	}
	
	
	function url_decrypt($string){
		$counter = 0;
		$data = str_replace(array('-','_','.'),array('+','/','='),$string);
		$mod4 = strlen($data) % 4;
		if ($mod4) {
		$data .= substr('====', $mod4);
		}
		$decrypted = base64_decode($data);
		
		$check = array('tran_id','order_id','amount','refcode','status');
		foreach($check as $str){
			str_replace($str,'',$decrypted,$count);
			if($count > 0){
				$counter++;
			}
		}
		if($counter === 5){
			return array('data'=>$decrypted , 'status'=>true);
		}else{
			return array('data'=>'' , 'status'=>false);
		}
	}
	
	public function postProcess(){
		if(Configuration::get('IPRESTA_irpul_DEBUG'))
			@ini_set('display_errors', 'on');
		
		// post and get
		//$this->tran_id 		= Tools::getValue('tran_id');
		$irpul_token  = Tools::getValue('irpul_token');
		$this->irpul_token = $irpul_token;
		
		$decrypted 		= $this->url_decrypt($irpul_token);
		if($decrypted['status']){
			parse_str($decrypted['data'], $ir_output);
			$this->tran_id 	= $ir_output['tran_id'];
			$this->order_id = $ir_output['order_id'];
			$this->amount 	= $ir_output['amount'];
			$this->refcode	= $ir_output['refcode'];
			$this->state 	= $ir_output['status'];
		}
	}
	
	/**
	 * @see FrontController::initContent()
	 */
	public function initContent(){
		parent::initContent();
		
		if(empty($this->order_id) || empty($this->state) || empty($this->tran_id)){
			$this->errors[] = $this->module->l('Payment Information is incorrect.');
		}
        elseif($this->state != 'paid'){
			$this->errors[] = $this->module->l('Payment failed.');
		}
		elseif(empty($this->context->cart->id)){
			$this->errors[] = $this->module->l('Your cart is empty.');
		}
			
		if(!count($this->errors)){
			if( $this->state == 'paid' ){
				$validate = $this->module->verify( $this->tran_id );
				 
				var_dump($validate);
				 
				if($validate === true){
					$paid = $this->module->validateOrder((int)$this->context->cart->id, _PS_OS_PAYMENT_, (float)$this->context->cart->getOrderTotal(true, 3), $this->module->displayName, $this->module->l('reference').': '.$this->tran_id , array(),(int)$this->context->currency->id, false, $this->context->customer->secure_key);

				}
				elseif($this->state >0 && $validate === false){
						$paid = $this->module->validateOrder((int)$this->context->cart->id, _PS_OS_ERROR_, (float)$this->context->cart->getOrderTotal(true, 3), $this->module->displayName, $this->module->l('reference').': '.$this->tran_id , array(),(int)$this->context->currency->id, false, $this->context->customer->secure_key);

				}
				$this->context->cookie->__unset("RefId");
				$this->context->cookie->__unset("amount");

				if(isset($paid) && $paid){
					Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$this->context->customer->secure_key.'&id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->module->id.'&id_order='.(int)$this->module->currentOrder.'&order_id='.$this->order_id.'&tran_id='.$this->tran_id);
				}
			}
       }
		$this->assignTpl();
	}
	
	public function assignTpl(){
		$this->context->smarty->assign(array(
            'access' 		=> 'denied',
            'ver' 			=> $this->module->version,
            'tran_id' 		=> $this->tran_id,
            'order_id' 		=> $this->order_id,
			'refcode' 		=> $this->refcode,
			'path' 			=> $this->module->displayName,
			'irpul_token' 	=> $this->irpul_token
		));
		return $this->setTemplate('validation.tpl');
	}
	
}