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
	
	public function postProcess(){
		if(Configuration::get('IPRESTA_irpul_DEBUG'))
			@ini_set('display_errors', 'on');
		
		// post and get
		//$this->tran_id 		= Tools::getValue('tran_id');
		
		//$irpul_token  = Tools::getValue('irpul_token');
		//$this->irpul_token = $irpul_token;
		
		$this->tran_id 	= Tools::getValue('tran_id');
		$this->order_id = Tools::getValue('order_id');
		$this->amount 	= Tools::getValue('amount');
		$this->refcode	= Tools::getValue('refcode');
		$this->state 	= Tools::getValue('status');
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