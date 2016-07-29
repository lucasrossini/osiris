<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de variação de produto.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 11/02/2014
	*/
	
	class ProductVariation extends \Database\DatabaseObject{
		const TABLE_NAME = 'ecom_product_variation';
		
		protected $variation_type;
		protected $variation;
		protected $variation_stock;
		protected $variation_sku;
		
		/**
		 * @see DatabaseObject::load()
		 */
		public function load($id, $autoload = false){
			if($record = parent::load($id, $autoload)){
				$this->variation_type = new VariationType($record->variation_type_id, $autoload);
				$this->variation_stock = (int)$record->variation_stock;
				
				return true;
			}
			
			return false;
		}
	}
?>