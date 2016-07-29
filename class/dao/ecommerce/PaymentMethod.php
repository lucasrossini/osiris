<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de forma de pagamento.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 10/02/2014
	*/
	
	class PaymentMethod extends \Database\DatabaseObject{
		const TABLE_NAME = 'ecom_payment_method';
		
		protected $name;
		protected $active;
	}
?>