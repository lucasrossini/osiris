<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para registro de pedido.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 13/03/2014
	*/
	
	class Order extends \Database\DatabaseObject{
		const TABLE_NAME = 'ecom_order';
		
		const LAST_ORDERS_INTERVAL = 30;
		
		const STATUS_AWAITING_PAYMENT = 0,
			  STATUS_AWAITING_DISPATCH = 1,
			  STATUS_DELIVERED = 2,
			  STATUS_CANCELLED = 3;
		
		protected $code;
		protected $client;
		protected $address;
		protected $payment_method;
		protected $shipping_method;
		protected $shipping_price;
		protected $total;
		protected $delivery_days;
		protected $delivery_date;
		protected $date;
		protected $time;
		protected $payment_date;
		protected $payment_time;
		protected $dispatch_date;
		protected $dispatch_time;
		protected $status;
		protected $gift;
		protected $tracking_code;
		protected $tracking_url;
		
		/**
		 * @see DatabaseObject::load()
		 */
		public function load($id, $autoload = false){
			if($record = parent::load($id, $autoload)){
				$this->client = new Client($record->client_id, $autoload);
				$this->address = new Address($record->address_id, $autoload);
				$this->payment_method = new PaymentMethod($record->payment_method_id, $autoload);
				$this->shipping_method = new ShippingMethod($record->shipping_method_id, $autoload);
				
				$this->code = '1'.\Formatter\Number::zero_padding($id, 6);
				$this->shipping_price = parent::create_money_obj($record->shipping_price);
				$this->total = parent::create_money_obj($record->total);
				
				$this->date = parent::create_date_obj($record->date);
				$this->time = parent::create_time_obj($record->time);
				
				$payment_datetime_pieces = explode(' ', $record->payment_datetime);
				$this->payment_date = parent::create_date_obj($payment_datetime_pieces[0]);
				$this->payment_time = parent::create_time_obj($payment_datetime_pieces[1]);
				
				$dispatch_datetime_pieces = explode(' ', $record->dispatch_datetime);
				$this->dispatch_date = parent::create_date_obj($dispatch_datetime_pieces[0]);
				$this->dispatch_time = parent::create_time_obj($dispatch_datetime_pieces[1]);
				
				$this->delivery_date = ($record->status == self::STATUS_DELIVERED) ? parent::create_date_obj(\DateTime\Date::add($this->dispatch_date->original, $record->delivery_days)) : null;
				$this->tracking_url = $record->tracking_code ? 'http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI='.$record->tracking_code : '';
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Carrega os produtos do pedido.
		 * 
		 * @return array Vetor multidimensional de produtos com os índices 'quantity', que indica a quantidade de itens daquele produto; 'price', que indica o preço do produto; 'object', que contém um objeto da classe Product; e 'variation', que contém um objeto da classe ProductVariation caso o produto possua variações.
		 */
		public function get_products(){
			global $db;
			$products = array();
			
			$db->query('SELECT product_id, quantity, variation_id, price FROM ecom_order_product WHERE order_id = '.$this->id);
			$order_products = $db->result();
			
			foreach($order_products as $order_product){
				$variation = $order_product->variation_id ? new ProductVariation($order_product->variation_id) : null;
				$products[] = array('quantity' => (int)$order_product->quantity, 'price' => $order_product->price, 'object' => new Product($order_product->product_id), 'variation' => $variation);
			}
			
			return $products;
		}
		
		/**
		 * Realiza o pedido.
		 * 
		 * @param int $payment_method_id ID da forma de pagamento.
		 * @param boolean $gift Define se deve ser embalado para presente.
		 * @return int ID do pedido realizado.
		 */
		public static function create($payment_method_id, $gift = false){
			global $db, $sys_user;
			
			//Instancia o carrinho
			$cart = new Cart();
			
			//Carrega os produtos do carrinho
			$cart_products = $cart->retrieve();
			
			//Carrega as opções de entrega
			$cart_shipping = $cart->get_shipping();
			
			//Registra o pedido
			$db->init_transaction();
			
			$client_id = $sys_user->get('id');
			$address_id = $cart_shipping['address'];
			$shipping_method_id = $cart_shipping['selected'];
			$shipping_price = $cart_shipping['methods'][$shipping_method_id]['price'];
			$delivery_days = $cart_shipping['methods'][$shipping_method_id]['delivery_days'];
			$cart_total = $cart->calculate_total();
			$total = ($cart_total + $shipping_price);
			$gift = (int)$gift;
			
			//Embalagem para presente
			if($gift){
				$settings = new Settings(1);
				$total += $settings->get('gift_price')->original;
			}

			$order_id = $db->query('INSERT INTO '.self::TABLE_NAME.' (client_id, address_id, payment_method_id, shipping_method_id, shipping_price, total, delivery_days, date, time, status, gift) VALUES ('.$client_id.', '.$address_id.', '.$payment_method_id.', '.$shipping_method_id.', "'.$shipping_price.'", "'.$total.'", '.$delivery_days.', CURDATE(), CURTIME(), '.self::STATUS_AWAITING_PAYMENT.', '.$gift.')');

			//Registra os produtos
			foreach($cart_products as $cart_product){
				$product = $cart_product['product'];
				$variation = $cart_product['variation'];
				$quantity = $cart_product['quantity'];
				
				$variation_id = $variation ? $variation->get('id') : 'NULL';
				$product_id = $product->get('id');
				
				$db->query('INSERT INTO ecom_order_product (order_id, product_id, variation_id, quantity, price) VALUES ('.$order_id.', '.$product_id.', '.$variation_id.', '.$quantity.', "'.$product->get('current_price')->original.'")');
				
				//Atualiza o estoque do produto
				$sql = $variation ? 'UPDATE '.ProductVariation::TABLE_NAME.' SET variation_stock = variation_stock - '.$quantity.' WHERE id = '.$variation_id : 'UPDATE '.Product::TABLE_NAME.' SET stock = stock - '.$quantity.' WHERE id = '.$product_id;
				$db->query($sql);
			}
			
			$transaction_result = $db->end_transaction();
			
			if($transaction_result['success']){
				//Envia e-mail de confirmação do pedido para o cliente
				Email::order_confirmation($order_id);
				
				return $order_id;
			}
			
			throw new \Exception('Ocorreu um erro ao realizar o seu pedido! Por favor, tente novamente.');
		}
		
		/**
		 * Retorna o nome do status de um pedido.
		 * 
		 * @param int $status Código do status.
		 * @return string Nome do status.
		 */
		public static function get_status_name($status){
			switch($status){
				case self::STATUS_AWAITING_PAYMENT:
					return 'Aguardando pagamento';
				
				case self::STATUS_AWAITING_DISPATCH:
					return 'Preparando para envio';
				
				case self::STATUS_DELIVERED:
					return 'Enviado';
				
				case self::STATUS_CANCELLED:
					return 'Cancelado';
			}
			
			return '';
		}
	}
?>