<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de subcategoria de produto.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 11/03/2014
	*/
	
	class Subcategory extends \Database\DatabaseObject{
		const TABLE_NAME = 'ecom_category';
		
		const BASE_PATH = '/categorias/';
		const PATH_SIZE = 3;
		
		const ADMIN_PACKAGE = 'categories';
		const ADMIN_MODULE = 'subcategories';
		
		//Sitemap
		public static $sitemap_data = array(
			'priority' => '0.70',
			'changefreq' => 'daily',
			'sql' => 'SELECT id FROM ecom_category WHERE parent_id IS NULL AND visible = 1'
		);
		
		protected $name;
		protected $url;
		protected $visible;
		protected $category;
		
		/**
		 * @see DatabaseObject::load()
		 */
		public function load($id, $autoload = false){
			if($record = parent::load($id, $autoload)){
				$this->category = new Category($record->parent_id, $autoload);
				$this->url = $this->category->get('url').'/'.$record->slug;
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Calcula o total de produtos da subcategoria.
		 * 
		 * @return int Total de produtos.
		 */
		public function get_products_count(){
			global $db;
			
			$db->query('SELECT COUNT(*) AS total FROM ecom_product_category pc, ecom_product p WHERE pc.product_id = p.id AND pc.category_id = '.$this->id.' AND p.visible = 1');
			return (int)$db->result(0)->total;
		}
		
		/**
		 * Verifica se a URL é um registro válido.
		 * 
		 * @param string $url URL a ser verificada.
		 * @return array|boolean Vetor com as informações da página caso seja uma URL válida ou FALSE caso seja uma URL inválida.
		 */
		public static function check_url($url){
			global $db;
			
			$url_pieces = parent::get_current_url_pieces($url);
			$category_slug = $url_pieces[0];
			$subcategory_slug = $url_pieces[1];
			
			$db->query('SELECT s.id, s.name FROM '.self::TABLE_NAME.' s, '.Category::TABLE_NAME.' c WHERE s.parent_id = c.id AND c.slug = "'.$category_slug.'" AND s.slug = "'.$subcategory_slug.'" AND c.visible = 1 AND s.visible = 1');

			if($db->row_count()){
				$subcategory = $db->result(0);
				return array('title' => $subcategory->name, 'subtitle' => '', 'file' => '/site/ecommerce/pages/category/details.php', 'show_title' => false, 'canonical' => true, 'record_id' => $subcategory->id);
			}
			
			return false;
		}
	}
?>