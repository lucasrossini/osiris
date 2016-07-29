<?php
	namespace DAO;
	
	/**
	 * Classe para registro de cidade.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 08/02/2012
	*/
	
	class City extends \Database\DatabaseObject{
		const TABLE_NAME = 'sys_city';
		
		protected $name;
		protected $state;
		
		/**
		 * @see DatabaseObject::load()
		 */
		public function load($id, $autoload = false){
			if($record = parent::load($id, $autoload)){
				$this->state = new State($record->state_id, $autoload);
				
				return true;
			}
			
			return false;
		}
	}
?>