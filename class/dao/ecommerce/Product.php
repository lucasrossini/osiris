<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de produto.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 14/03/2014
	*/
	
	class Product extends \Database\DatabaseObject{
		const TABLE_NAME = 'ecom_product';
		
		const BASE_PATH = '/produtos/';
		const PATH_SIZE = 2;
		
		const ADMIN_PACKAGE = 'products';
		const ADMIN_MODULE = 'products';
		
		//Sitemap
		public static $sitemap_data = array(
			'priority' => '0.90',
			'changefreq' => 'daily',
			'sql' => 'SELECT id FROM ecom_product WHERE visible = 1'
		);
		
		const ORDER_ALPHA = 'alpha',
			  ORDER_PRICE_ASC = 'price_asc',
			  ORDER_PRICE_DESC = 'price_desc',
			  ORDER_RECENT = 'recent',
			  ORDER_MOST_SOLD = 'most_sold';
		
		const VIEW_SESSION = 'products_view_log';
		
		protected static $facebook_data = array('title' => 'name', 'type' => 'product', 'description' => 'headline', 'image' => 'image', 'url' => 'url');
		protected static $gplus_data = array('name' => 'name', 'image' => 'image', 'description' => 'headline');
		
		protected $code;
		protected $name;
		protected $description;
		protected $headline;
		protected $visible;
		protected $image;
		protected $url;
		
		protected $price;
		protected $promotional_price;
		protected $current_price;
		protected $discount_value;
		protected $discount_percentage;
		
		protected $sku;
		protected $stock;
		protected $order_limit;
		
		protected $weight;
		protected $length;
		protected $width;
		protected $height;
		protected $free_shipping;
		
		protected $date;
		protected $time;
		
		protected $shipping;
		
		/**
		 * @see DatabaseObject::load()
		 */
		public function load($id, $autoload = false){
			if($record = parent::load($id, $autoload)){
				$this->code = '1'.\Formatter\Number::zero_padding($id, 6);
				$this->headline = \Formatter\String::truncate($record->description, 100);
				$this->price = parent::create_money_obj($record->price);
				$this->promotional_price = parent::create_money_obj($record->promotional_price);
				
				if($record->promotional_price){
					$this->current_price = $this->promotional_price;
					$this->discount_value = parent::create_money_obj($record->price - $record->promotional_price);
					$this->discount_percentage = round($this->discount_value->original * 100 / $record->price);
				}
				else{
					$this->current_price = $this->price;
					$this->discount_value = $this->discount_percentage = 0;
				}
				
				$this->stock = (int)$record->stock;
				$this->url = self::BASE_PATH.$record->slug;
				$this->image = $record->image ? '/uploads/ecommerce/images/products/'.$record->image : '';
				
				//Frete
				$cookie_name = 'product_'.$id.'_shipping';
				$this->shipping = \HTTP\Cookie::exists($cookie_name) ? unserialize(\HTTP\Cookie::get($cookie_name)) : array();
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Verifica se um produto está em estoque.
		 * 
		 * @param int $variation_id ID da variação do produto.
		 * @return boolean TRUE caso esteja em estoque ou FALSE caso contrário.
		 */
		public function in_stock($variation_id = null){
			if($variation_id){
				$product_variation = new ProductVariation($variation_id);
				return ($product_variation->get('variation_stock') > 0);
			}
			
			return ($this->stock > 0);
		}
		
		/**
		 * Verifica se o produto está fora de estoque.
		 * 
		 * @return boolean TRUE caso esteja fora de estoque ou FALSE caso contrário.
		 */
		public function is_out_of_stock(){
			//Produto sem variações
			if($this->stock)
				return false;
			
			//Variações
			$variations = $this->get_variations();
			
			foreach($variations as $variation){
				if($variation->get('variation_stock'))
					return false;
			}
			
			return true;
		}
		
		/**
		 * Retorna a tag de imagem do produto.
		 * 
		 * @return string Tag HTML da imagem.
		 */
		public function get_img_tag($width, $height){
			$width = (int)$width;
			$height = (int)$height;
			
			return '<img data-original="'.\Media\Image::thumb($this->get('image'), $width, $height).'" class="lazy" alt="'.$this->get('name').'" width="'.$width.'" height="'.$height.'" />';
		}
		
		/**
		 * Carrega as fotos do produto.
		 * 
		 * @return array Vetor de fotos com os índices 'file', que indica o caminho do arquivo; e 'subtitle', que indica a legenda da foto.
		 */
		public function get_photos(){
			global $db;
			$result = array();
			
			$db->query('SELECT file, subtitle FROM ecom_product_photo WHERE product_id = '.$this->id);
			$photos = $db->result();
			
			foreach($photos as $photo)
				$result[] = array('file' => '/uploads/ecommerce/images/products/gallery/'.$photo->file, 'subtitle' => $photo->subtitle);
			
			return $result;
		}
		
		/**
		 * Carrega as variações do produto
		 * 
		 * @return array Vetor de objetos da classe ProductVariation.
		 */
		public function get_variations(){
			global $db;
			$variations = array();
			
			$db->query('SELECT id FROM ecom_product_variation WHERE product_id = '.$this->id.' ORDER BY variation_type_id, variation');
			$product_variations = $db->result();
			
			foreach($product_variations as $product_variation)
				$variations[] = new ProductVariation($product_variation->id);
			
			return $variations;
		}
		
		/**
		 * Carrega as categorias do produto
		 * 
		 * @return array Vetor multidimensional de categorias/subcategorias do produto, com os índices 'category', que contém o objeto da categoria; e 'subcategories', que contém um vetor de objetos das subcategorias dessa categoria.
		 */
		public function get_categories(){
			global $db;
			$categories = array();
			
			$db->query('SELECT c.id FROM ecom_category c WHERE c.parent_id IS NULL AND c.id IN (SELECT pc.category_id FROM ecom_product_category pc WHERE pc.product_id = '.$this->id.')');
			$product_categories = $db->result();
			
			foreach($product_categories as $product_category){
				//Subcategorias
				$subcategories = array();
				
				$db->query('SELECT s.id FROM ecom_category s WHERE s.parent_id = '.$product_category->id.' AND s.id IN (SELECT c.category_id FROM ecom_product_category c WHERE c.product_id = '.$this->id.')');
				$product_subcategories = $db->result();
				
				if(sizeof($product_subcategories)){
					foreach($product_subcategories as $product_subcategory)
						$subcategories[] = new Subcategory($product_subcategory->id);
				}
				
				$categories[] = array('category' => new Category($product_category->id), 'subcategories' => $subcategories);
			}
			
			return $categories;
		}
		
		/**
		 * Carrega as tags do produto
		 * 
		 * @return array Vetor de objetos da classe Tag.
		 */
		public function get_tags(){
			global $db;
			$tags = array();
			
			$db->query('SELECT tag_id FROM ecom_product_tag WHERE product_id = '.$this->id);
			$product_tags = $db->result();
			
			foreach($product_tags as $product_tag)
				$tags[] = new Tag($product_tag->tag_id);
			
			return $tags;
		}
		
		/**
		 * Registra a visualização do produto por um cliente.
		 */
		public function log_view(){
			global $db, $sys_user;
			$products_views = \HTTP\Session::exists(self::VIEW_SESSION) ? \HTTP\Session::get(self::VIEW_SESSION) : array();
			
			if($sys_user->is_logged() && !in_array($this->id, $products_views)){
				$db->query('INSERT INTO ecom_product_view (client_id, product_id, date, time) VALUES ('.$sys_user->get('id').', '.$this->id.', CURDATE(), CURTIME())');
				$products_views[] = $this->id;
			}
			
			\HTTP\Session::create(self::VIEW_SESSION, $products_views);
		}
		
		/**
		 * Calcula o frete do produto.
		 * 
		 * @param string $zip_code CEP de destino.
		 */
		public function calculate_shipping($zip_code){
			$shipping = array('zip_code' => $zip_code, 'methods' => array());
			
			if($this->get('free_shipping')){
				//Frete grátis
				$shipping_method = new ShippingMethod(ShippingMethod::FREE_SHIPPING_ID);
				
				$shipping['methods'][$shipping_method->get('id')] = array(
					'name' => $shipping_method->get('name'),
					'price' => 0,
					'delivery_days' => $shipping_method->get('delivery_days')
				);
			}
			else{
				//Carrega os métodos de envio
				$shipping_methods = \DAO\Ecommerce\ShippingMethod::load_all('SELECT id FROM '.\DAO\Ecommerce\ShippingMethod::TABLE_NAME.' WHERE active = 1 AND id != '.ShippingMethod::FREE_SHIPPING_ID);
				
				$settings = new Settings(1);
				$origin_zip_code = $settings->get('zip_code');
				
				//Monta o pacote
				$package = new \Correios\Package();
				$package->add_item(new \Correios\Item($this->get('weight') / 1000, $this->get('length'), $this->get('width'), $this->get('height')));

				foreach($shipping_methods['results'] as $shipping_method){
					$add = false;

					if($shipping_method->get('correios_code')){
						$response = \Correios\Shipping::calculate($shipping_method->get('correios_code'), $origin_zip_code, $zip_code, $package->get_dimensions(), $this->get('current_price')->original);

						if($response['success']){
							$add = true;
							$name = \Correios\Shipping::get_name($shipping_method->get('correios_code'));
							$price = $response['value'];
							$delivery_days = (int)$response['delivery_days'];
						}
					}
					else{
						$add = true;
						$name = $shipping_method->get('name');
						$delivery_days = $shipping_method->get('delivery_days');
						$price = $shipping_method->get('price')->original;
					}

					if($add){
						$shipping['methods'][$shipping_method->get('id')] = array(
							'name' => $name,
							'price' => $price,
							'delivery_days' => $delivery_days
						);
					}
				}
			}
			
			//Salva o frete calculado
			$this->shipping = $shipping;
			\HTTP\Cookie::create('product_'.$this->id.'_shipping', serialize($this->shipping));
		}
		
		/**
		 * Carrega as opções de ordenação de produtos.
		 * 
		 * @return array Vetor de opções.
		 */
		public static function get_order_options(){
			return array(
				\DAO\Ecommerce\Product::ORDER_ALPHA => 'A-Z',
				\DAO\Ecommerce\Product::ORDER_PRICE_ASC => 'Menor preço',
				\DAO\Ecommerce\Product::ORDER_PRICE_DESC => 'Maior preço',
				\DAO\Ecommerce\Product::ORDER_RECENT => 'Mais recentes',
				\DAO\Ecommerce\Product::ORDER_MOST_SOLD => 'Mais vendidos'
			);
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
			
			$db->query('SELECT id, name FROM '.self::TABLE_NAME.' WHERE slug = "'.$slug.'" AND visible = 1');

			if($db->row_count()){
				$product = $db->result(0);
				return array('title' => $product->name, 'subtitle' => '', 'file' => '/site/ecommerce/pages/product/details.php', 'show_title' => false, 'record_id' => $product->id);
			}
			
			return false;
		}
		
		/*---- Métodos customizáveis ----*/
		
		/**
		 * Retorna o HTML do produto.
		 * 
		 * @return string HTML do produto.
		 */
		public function get_html(){
			if($this->get('promotional_price')){
				$price_html = '
					<del class="original">'.$this->get('price')->formatted.'</del>
					<ins class="promotional">'.$this->get('promotional_price')->formatted.'</ins>
					<span class="discount">'.$this->get('discount_percentage').'% de desconto</span>
				';
			}
			else{
				$price_html = '<span class="default">'.$this->get('price')->formatted.'</span>';
			}
			
			$details_button_html = !$this->is_out_of_stock() ? '<a href="'.$this->get('url').'" class="details">Ver detalhes</a>' : '<span class="out-of-stock">Esgotado</span>';
			
			$html = '
				<div class="product" data-id="'.$this->get('id').'">
					<a href="'.$this->get('url').'" class="image">'.$this->get_img_tag(150, 150).'</a>
					<a href="'.$this->get('url').'" class="name">'.$this->get('name').'</a>
					<div class="price">'.$price_html.'</div>
					'.$details_button_html.'
				</div>
			';
			
			return $html;
		}
	}
?>