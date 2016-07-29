<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de endereço de usuário.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 26/02/2014
	*/
	
	class Address extends \Database\DatabaseObject{
		const TABLE_NAME = 'ecom_address';
		
		protected $client;
		protected $title;
		protected $addressee;
		protected $street;
		protected $number;
		protected $complement;
		protected $neighborhood;
		protected $zip_code;
		protected $state;
		protected $city;
		protected $default;
		
		/**
		 * @see DatabaseObject::load()
		 */
		public function load($id, $autoload = false){
			if($record = parent::load($id, $autoload)){
				$this->client = new \DAO\Ecommerce\Client($record->client_id, $autoload);
				$this->state = new \DAO\State($record->state_id, $autoload);
				$this->city = new \DAO\City($record->city_id, $autoload);
				
				return true;
			}
			
			return false;
		}
	}
?>