<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de tag.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 21/02/2014
	*/
	
	class Tag extends \Database\DatabaseObject{
		const TABLE_NAME = 'ecom_tag';
		
		const BASE_PATH = '/tag/';
		const PATH_SIZE = 2;
		
		//Sitemap
		public static $sitemap_data = array(
			'priority' => '0.60',
			'changefreq' => 'daily'
		);
		
		protected $tag;
		protected $url;
		
		/**
		 * @see DatabaseObject::load()
		 */
		public function load($id, $autoload = false){
			if($record = parent::load($id, $autoload)){
				$this->url = self::BASE_PATH.$record->slug;
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Carrega as tags mais utilizadas.
		 * 
		 * @param int $count Quantidade de tags a ser carregada.
		 * @return array Vetor multidimensional de tags, com os índices 'tag', que indica o objeto da tag; e 'count', que indica a quantidade de utilizações da tag.
		 */
		public static function get_cloud($count = 10){
			global $db;
			$tag_cloud = array();
			
			$db->query('SELECT t.id, (SELECT COUNT(*) FROM ecom_product_tag pt WHERE pt.tag_id = t.id) AS total FROM '.self::TABLE_NAME.' t ORDER BY total DESC LIMIT 0,'.(int)$count);
			$tags = $db->result();
			
			foreach($tags as $tag)
				$tag_cloud[] = array('tag' => new self($tag->id), 'count' => $tag->count);
			
			return $tag_cloud;
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
			$slug = $url_pieces[0];
			
			$db->query('SELECT id, tag FROM '.self::TABLE_NAME.' WHERE slug = "'.$slug.'"');

			if($db->row_count()){
				$tag = $db->result(0);
				return array('title' => 'Produtos com a tag "'.$tag->tag.'"', 'subtitle' => '', 'file' => '/site/ecommerce/pages/tag/details.php', 'show_title' => false, 'canonical' => true, 'record_id' => $tag->id);
			}
			
			return false;
		}
	}
?>