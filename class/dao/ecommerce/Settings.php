<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de configurações da loja.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 06/03/2014
	*/
	
	class Settings extends \Database\DatabaseObject{
		const TABLE_NAME = 'ecom_settings';
		
		protected $zip_code;
		protected $gift_price;
		
		/**
		 * @see DatabaseObject::load()
		 */
		public function load($id, $autoload = false){
			if($record = parent::load($id, $autoload)){
				$this->gift_price = parent::create_money_obj($record->gift_price);
				
				return true;
			}
			
			return false;
		}
	}
?>