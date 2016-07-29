<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de tipo de variação.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 06/02/2014
	*/
	
	class VariationType extends \Database\DatabaseObject{
		const TABLE_NAME = 'ecom_variation_type';
		
		protected $name;
	}
?>