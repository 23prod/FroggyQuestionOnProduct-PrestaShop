<?php

class FroggyQuestionOnProductFormModuleFrontController extends ModuleFrontController
{

	public function __construct()
	{
		parent::__construct();

		$this->context = Context::getContext();
	}

	public function init()
	{
		parent::init();
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitQuestion')) {
			if (Validate::isInt(Tools::getValue('id_product'))) {
				$product = new Product(Tools::getValue('id_product'));
				if (!Validate::isLoadedObject($product)) {
					$this->errors[] = $this->module->l('Product ID is incorrect');
				}
			} else {
				$this->errors[] = $this->module->l('Product ID is incorrect');
			}

			if (!$this->module->isCustomerLogged() && !Validate::isEmail(Tools::getValue('email'))) {
				$this->errors[] = $this->module->l('Your email is invalid');
			}

			if (!Validate::isCleanHtml(Tools::getValue('message')) || !Tools::getValue('message')) {
				$this->errors[] = $this->module->l('Message field is invalid');
			}

			if (!count($this->errors)) {
				// Create Customer Thread
				$ct = new CustomerThread();
				if (isset($this->context->customer->id) && $this->context->customer->id)
					$ct->id_customer = $this->context->customer->id;
				$ct->id_shop = (int)$this->context->shop->id;
				$ct->id_product = Tools::getValue('id_product');
				$ct->id_contact = Configuration::get('FC_QOP_CONTACT_ID');
				$ct->id_lang = (int)$this->context->language->id;
				if ($this->module->isCustomerLogged()) {
					$ct->email = $this->context->customer->email;
				} else {
					$ct->email = Tools::getValue('email');
				}
				$ct->status = 'open';
				$ct->token = Tools::passwdGen(12);
				if ($ct->add()) {
					// Prepare message
					$message = $this->module->l('A customer have a question about one product...');
					$message .= "\n\n".'---'."\n\n";
					$message .= Tools::htmlentitiesUTF8(Tools::getValue('message'));

					$cm = new CustomerMessage();
					$cm->id_customer_thread = $ct->id;
					$cm->message = $message;
					$cm->ip_address = ip2long($_SERVER['REMOTE_ADDR']);
					$cm->user_agent = $_SERVER['HTTP_USER_AGENT'];
					if ($cm->add()) {
						$this->context->smarty->assign('success', true);
					} else {
						$this->errors[] = Tools::displayError('An error occurred while sending the message.');
					}
				} else {
					$this->errors[] = Tools::displayError('An error occurred while sending the message.');
				}
			}
		}

		parent::postProcess();
	}

	public function initContent()
	{
		parent::initContent();

		$product = new Product(Tools::getValue('id_product'), false, $this->context->language->id);
		$image = Product::getCover($product->id);
		$product->id_image = $image['id_image'];
		$this->context->smarty->assign(array(
			'isLogged' => $this->module->isCustomerLogged(),
			'id_product' => Tools::getValue('id_product'),
			'product' => $product
		));

		return $this->setTemplate('form.tpl');
	}

}