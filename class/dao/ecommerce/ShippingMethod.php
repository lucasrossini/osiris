<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de tipo de envio.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 06/03/2014
	*/
	
	class ShippingMethod extends \Database\DatabaseObject{
		const TABLE_NAME = 'ecom_shipping_method';
		
		const PER_PRODUCT = 1;
		const PER_ORDER = 2;
		
		const FREE_SHIPPING_ID = 4;
		
		protected $name;
		protected $price;
		protected $unit;
		protected $active;
		protected $correios_code;
		protected $delivery_days;
		
		/**
		 * @see DatabaseObject::load()
		 */
		public function load($id, $autoload = false){
			if($record = parent::load($id, $autoload)){
				$this->price = parent::create_money_obj($record->price);
				
				return true;
			}
			
			return false;
		}
	}
?>