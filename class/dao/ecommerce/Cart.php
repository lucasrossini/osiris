<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de carrinho de compras.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 19/03/2014
	*/
	
	class Cart{
		private $id;
		private $products;
		private $shipping;
		
		/**
		 * Construtor do carrinho.
		 * 
		 * @param mixed $id ID do carrinho.
		 */
		public function __construct($id = 'cart'){
			$this->id = $id ? $id : session_id();
			$cookie = \HTTP\Cookie::exists($this->id) ? unserialize(\HTTP\Cookie::get($this->id)) : array();
			
			$this->products = is_array($cookie['products']) ? $cookie['products'] : array();
			$this->shipping = is_array($cookie['shipping']) ? $cookie['shipping'] : array();
		}
		
		/**
		 * Calcula o total de itens de um produto no carrinho.
		 * 
		 * @param int $product_id ID do produto.
		 * @return int Total de itens do produto.
		 */
		private function count_product_items($product_id){
			$count = 0;
			
			foreach($this->products as $current_product_id => $current_variations){
				if($current_product_id == $product_id){
					foreach($current_variations as $current_variation_id => $current_variation_quantity)
						$count += $current_variation_quantity;
				}
			}
			
			return $count;
		}
		
		/**
		 * Adiciona produto ao carrinho.
		 * 
		 * @param int $product_id ID do produto.
		 * @param int $variation_id ID da variação do produto.
		 * @param int $quantity Quantidade de itens do produto.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function add($product_id, $variation_id = null, $quantity = 1){
			//Verifica se é um produto válido
			$product = new Product($product_id);
			$valid_variation = true;
			
			if($variation_id){
				$product_variation = new ProductVariation($variation_id);
				$valid_variation = $product_variation->get('valid');
			}
			
			if($product->get('valid') && $valid_variation && $product->in_stock($variation_id)){
				//Carrega a quantidade já existente de itens do produto no carrinho
				$variation_id = (int)$variation_id;
				$product_quantity = (array_key_exists($product_id, $this->products) && array_key_exists($variation_id, $this->products[$product_id])) ? $this->products[$product_id][$variation_id] : 0;
				
				//Verifica o limite de vendas do produto em uma compra
				$product_items_count = $quantity;
				
				if(sizeof($this->products[$product_id]))
					$product_items_count += $this->count_product_items($product_id);
				
				if(($product->get('order_limit') > 0) && ($product_items_count > $product->get('order_limit')))
					throw new \Exception('Limite máximo de itens do produto excedido!');
				
				//Adiciona o produto
				$this->products[$product_id][$variation_id] = $product_quantity + $quantity;
				
				//Recalcula o frete
				if(sizeof($this->shipping))
					$this->calculate_shipping($this->shipping['zip_code']);

				return $this->save();
			}
			
			throw new \Exception('Produto inválido ou fora de estoque!');
		}
		
		/**
		 * Subtrai quantidade do produto no carrinho.
		 * 
		 * @param int $product_id ID do produto.
		 * @param int $variation_id ID da variação do produto.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function subtract($product_id, $variation_id = null){
			$variation_id = (int)$variation_id;
			
			if(array_key_exists($product_id, $this->products) && array_key_exists($variation_id, $this->products[$product_id])){
				$this->products[$product_id][$variation_id]--;
				
				//Retira do carrinho caso possua 0 itens
				if(!$this->products[$product_id][$variation_id])
					unset($this->products[$product_id][$variation_id]);
				
				if(!sizeof($this->products[$product_id]))
					unset($this->products[$product_id]);
				
				//Recalcula o frete
				if(!sizeof($this->products))
					$this->shipping = array();
				
				if(sizeof($this->shipping))
					$this->calculate_shipping($this->shipping['zip_code']);
				
				return $this->save();
			}
			
			return false;
		}
		
		/**
		 * Remove produto do carrinho.
		 * 
		 * @param int $product_id ID do produto.
		 * @param int $variation_id ID da variação do produto.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function delete($product_id, $variation_id = null){
			$variation_id = (int)$variation_id;
			
			if(array_key_exists($product_id, $this->products) && array_key_exists($variation_id, $this->products[$product_id])){
				unset($this->products[$product_id][$variation_id]);
				
				if(!sizeof($this->products[$product_id]))
					unset($this->products[$product_id]);
				
				//Recalcula o frete
				if(!sizeof($this->products))
					$this->shipping = array();
				
				if(sizeof($this->shipping))
					$this->calculate_shipping($this->shipping['zip_code']);
				
				return $this->save();
			}
			
			return false;
		}
		
		/**
		 * Define a quantidade de itens de um produto.
		 * 
		 * @param int $product_id ID do produto.
		 * @param int $variation_id ID da variação do produto.
		 * @param int $quantity Quantidade de itens do produto.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function update($product_id, $variation_id = null, $quantity = 1){
			if($quantity > 0){
				unset($this->products[$product_id][(int)$variation_id]);
				return $this->add($product_id, $variation_id, $quantity);
			}
			
			return $this->delete($product_id, $variation_id);
		}
		
		/**
		 * Limpa o carrinho.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function clear(){
			$this->products = $this->shipping = array();
			return $this->save();
		}
		
		/**
		 * Retorna a lista de produtos do carrinho.
		 * 
		 * @return array Vetor multidimensional de produtos com os índices 'product', que indica o objeto do produto; 'variation', que indica o objeto da variação do produto; e 'quantity', que indica a quantidade de itens do produto.
		 */
		public function retrieve(){
			$products = array();
			
			foreach($this->products as $product_id => $variations){
				foreach($variations as $variation_id => $quantity){
					$variation = $variation_id ? new ProductVariation($variation_id) : null;
					$products[] = array('product' => new Product($product_id), 'variation' => $variation, 'quantity' => $quantity);
				}
			}
			
			return $products;
		}
		
		/**
		 * Verifica o estoque dos produtos no carrinho e remove os que já esgotaram.
		 * 
		 * @return array Vetor de produtos removidos (vide Cart::retrieve()).
		 */
		public function check_products_stock(){
			$cart_products = $this->retrieve();
			$out_of_stock_products = array();

			foreach($cart_products as $cart_product){
				$product = $cart_product['product'];
				$product_variation = $cart_product['variation'];
				$quantity = $cart_product['quantity'];

				if($product_variation){
					$stock = $product_variation->get('variation_stock');
					$variation_id = $product_variation->get('id');
				}
				else{
					$stock = $product->get('stock');
					$variation_id = null;
				}

				if($stock < $quantity){
					$out_of_stock_products[] = $cart_product;
					$this->delete($product->get('id'), $variation_id);
				}
			}
			
			return $out_of_stock_products;
		}
		
		/**
		 * Verifica se o carrinho está vazio.
		 * 
		 * @return boolean TRUE caso esteja vazio ou FALSE caso contrário.
		 */
		public function is_empty(){
			return !sizeof($this->products);
		}
		
		/**
		 * Retorna o total de produtos distintos do carrinho.
		 * 
		 * @return int Total de produtos.
		 */
		public function get_count(){
			$count = 0;
			
			foreach($this->products as $variations)
				$count += sizeof($variations);
			
			return $count;
		}
		
		/**
		 * Calcula o valor total dos produtos do carrinho.
		 * 
		 * @return float Valor total.
		 */
		public function calculate_total(){
			$total = 0;
			
			foreach($this->products as $product_id => $variations){
				foreach($variations as $quantity){
					$product = new Product($product_id);
					$total += $product->get('current_price')->original * $quantity;
				}
			}
			
			return $total;
		}
		
		/**
		 * Calcula o frete dos produtos do carrinho.
		 * 
		 * @param string $zip_code CEP de destino.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function calculate_shipping($zip_code){
			$last_selected_shipping = $this->shipping['selected'];
			$this->shipping = array();
			
			if(!$this->is_empty()){
				$this->shipping = array('zip_code' => $zip_code, 'methods' => array());
				
				//Carrega os métodos de envio
				$shipping_methods = \DAO\Ecommerce\ShippingMethod::load_all('SELECT id FROM '.\DAO\Ecommerce\ShippingMethod::TABLE_NAME.' WHERE active = 1 AND id != '.ShippingMethod::FREE_SHIPPING_ID);
				
				//Monta o pacote e calcula o valor total do carrinho
				$package = new \Correios\Package();
				$total = $items_count = 0;
				
				foreach($this->products as $product_id => $variations){
					foreach($variations as $quantity){
						$product = new Product($product_id);
						
						if(!$product->get('free_shipping')){
							$total += $product->get('current_price')->original * $quantity;
							$items_count += $quantity;

							for($i = 1; $i <= $quantity; $i++)
								$package->add_item(new \Correios\Item($product->get('weight') / 1000, $product->get('length'), $product->get('width'), $product->get('height')));
						}
					}
				}
				
				//Calcula o frete
				if($items_count){
					$settings = new Settings(1);
					$origin_zip_code = $settings->get('zip_code');
					
					foreach($shipping_methods['results'] as $shipping_method){
						$add = false;

						if($shipping_method->get('correios_code')){
							$response = \Correios\Shipping::calculate($shipping_method->get('correios_code'), $origin_zip_code, $zip_code, $package->get_dimensions(), $total);

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

							switch($shipping_method->get('unit')){
								case ShippingMethod::PER_PRODUCT:
									$price = $shipping_method->get('price')->original * $items_count;
									break;

								case ShippingMethod::PER_ORDER:
									$price = $shipping_method->get('price')->original;
									break;
							}
						}

						if($add){
							$this->shipping['methods'][$shipping_method->get('id')] = array(
								'name' => $name,
								'price' => $price,
								'delivery_days' => $delivery_days
							);
						}
					}
					
					//Seleciona o método de envio anterior se ele ainda estiver disponível
					if(array_key_exists($last_selected_shipping, $this->shipping['methods']))
						$this->shipping['selected'] = $last_selected_shipping;
				}
				else{
					$shipping_method = new ShippingMethod(ShippingMethod::FREE_SHIPPING_ID);
					
					$this->shipping['methods'][$shipping_method->get('id')] = array(
						'name' => $shipping_method->get('name'),
						'price' => 0,
						'delivery_days' => $shipping_method->get('delivery_days')
					);
					
					$this->shipping['selected'] = $shipping_method->get('id');
				}
			}
			
			return $this->save();
		}
		
		/**
		 * Retorna as opções de envio dos produtos do carrinho.
		 * 
		 * @return array Vetor multidimensional com as formas de envio e as opções selecionadas, com os índices 'selected', que indica o ID da forma de envio selecionada; 'address', que indica o ID do endereço selecionado para entrega; e 'methods', que contém um vetor multidimensional onde o índice é o ID da forma de envio e o valor é um vetor com os índices 'name', que indica o nome da forma de envio; 'price', que indica o valor do frete; e 'delivery_days', que indica o prazo de entrega.
		 */
		public function get_shipping(){
			return $this->shipping;
		}
		
		/**
		 * Seleciona o método de envio.
		 * 
		 * @param int $id ID do método.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function select_shipping($id){
			if(array_key_exists($id, $this->shipping['methods']))
				$this->shipping['selected'] = $id;
			else
				unset($this->shipping['selected']);
			
			return $this->save();
		}
		
		/**
		 * Seleciona o endereço de entrega.
		 * 
		 * @param int $id ID do endereço.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function select_address($id){
			if($id)
				$this->shipping['address'] = $id;
			else
				unset($this->shipping['address']);
			
			return $this->save();
		}
		
		/**
		 * Salva os produtos do carrinho.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		private function save(){
			$cookie = array('products' => $this->products, 'shipping' => $this->shipping);
			return \HTTP\Cookie::create($this->id, serialize($cookie));
		}
		
		/**
		 * Monta e retorna link para o carrinho com a lista de produtos adicionados (para utilizar no menu).
		 * 
		 * @param string $label Rótulo do link.
		 * @return string HTML do link.
		 */
		public function get_jump_menu($label = 'Carrinho'){
			global $sys_assets;
			
			//Carrega os recursos necessários
			$sys_assets->load('css', 'site/ecommerce/assets/js/jscrollpane/jquery.jscrollpane.css');
			$sys_assets->load('js', 'site/ecommerce/assets/js/jscrollpane/jquery.mousewheel.js');
			$sys_assets->load('js', 'site/ecommerce/assets/js/jscrollpane/jquery.jscrollpane.min.js');
			
			//Lista de produtos
			$cart_products = $this->retrieve();
			
			if(sizeof($cart_products)){
				$menu = '<ul>';
				
				foreach($cart_products as $cart_product){
					$product = $cart_product['product'];
					$variation = $cart_product['variation'];
					$quantity = $cart_product['quantity'];
					
					$variation_html = $variation ? '<span class="variation">'.$variation->get('variation_type')->get('name').': '.$variation->get('variation').'</span>' : '';
					
					$menu .= '
						<li>
							<a href="'.$product->get('url').'" class="image">'.$product->get_img_tag(80, 80).'</a>
							
							<div class="details">
								<a href="'.$product->get('url').'" class="name">'.$product->get('name').'</a>
								'.$variation_html.'
								<span class="quantity">Quantidade: '.$quantity.'</span>
								<span class="price">'.\Formatter\Number::money($quantity * $product->get('current_price')->original).'</span>
							</div>
						</li>
					';
				}
				
				$menu .= '
					</ul>
					
					<div class="footer">
						<div class="subtotal">
							<span class="name">Subtotal</span>
							<span class="value">'.\Formatter\Number::money($this->calculate_total()).'</span>
						</div>
						
						<a href="/checkout" class="checkout">Finalizar compra</a>
					</div>
				';
			}
			else{
				$menu = '
					<p class="empty">
						Seu carrinho está vazio!
						<a href="/produtos">Confira nossos produtos</a>
					</p>
				';
			}
			
			//HTML
			return '
				<span id="cart-jump-menu">
					<a href="/carrinho" title="Ir para o carrinho de compras">'.$label.' ('.$this->get_count().')</a>
					<div class="menu">'.$menu.'</div>
				</span>
				
				<script>
					//Scroll customizado
					$(document).ready(function(){
						$("#cart-jump-menu > .menu > ul").jScrollPane({
							mouseWheelSpeed: 50,
							animateScroll: true
						});
					});
				</script>
			';
		}
	}
?>