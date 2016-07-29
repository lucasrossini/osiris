<link rel="stylesheet" href="/site/ecommerce/assets/css/payment.css" />

<section id="payment">
	<?php
		//Instancia o carrinho
		$cart = new DAO\Ecommerce\Cart();

		//Redireciona para a página de carrinho se não houver produtos
		if($cart->is_empty())
			URL\URL::redirect('/carrinho');
		
		//Verifica novamente o estoque dos produtos no carrinho
		$out_of_stock_products = $cart->check_products_stock();
		
		if(sizeof($out_of_stock_products)){
			$error_message = '
				<p>Os produtos abaixo estão fora de estoque e foram removidos do seu carrinho:</p>
				<ul>
			';
			
			foreach($out_of_stock_products as $out_of_stock_product){
				$product = $out_of_stock_product['product'];
				$product_variation = $out_of_stock_product['variation'];
				
				$variation_suffix = $product_variation ? '('.$product_variation->get('variation_type')->get('name').': '.$product_variation->get('variation').')' : '';
				$error_message .= '<li>'.$product->get('name').$variation_suffix.'</li>';
			}
			
			$error_message .= '</ul>';
			UI\Message::error($error_message);
			\URL\URL::redirect('/carrinho');
		}
		
		try{
			//Registra o pedido
			$order_id = \DAO\Ecommerce\Order::create(1, \HTTP\Request::post('gift'));
			HTTP\Session::create('order_placed', $order_id);
			
			//Dados do carrinho
			$cart_products = $cart->retrieve();
			$cart_shipping = $cart->get_shipping();

			$client_id = $sys_user->get('id');
			$address_id = $cart_shipping['address'];
			$shipping_method_id = $cart_shipping['selected'];
			$shipping_price = $cart_shipping['methods'][$shipping_method_id]['price'];
			
			/*---- PagSeguro ----*/
			
			//Produtos
			$products = array();

			foreach($cart_products as $cart_product){
				$product = $cart_product['product'];
				$variation = $cart_product['variation'];
				$quantity = $cart_product['quantity'];

				$variation_suffix = $variation ? ' - '.$variation->get('variation_type')->get('name').': '.$variation->get('variation') : '';

				$products[] = array(
					'id' => $product->get('id'),
					'name' => $product->get('name').$variation_suffix,
					'price' => $product->get('current_price')->original,
					'quantity' => $quantity
				);
			}

			//Dados do comprador
			$client = new \DAO\Ecommerce\Client($client_id);
			$buyer_info = array('name' => $client->get('name'), 'email' => $client->get('email'), 'phone' => $client->get('phone'));

			//Endereço de entrega e frete
			$shipping_method = new \DAO\Ecommerce\ShippingMethod($shipping_method_id);
			$address = new \DAO\Ecommerce\Address($address_id);

			switch($shipping_method->get('correios_code')){
				case \Correios\Shipping::PAC:
				case \Correios\Shipping::SEDEX:
					$type = strtoupper(\Correios\Shipping::get_name($shipping_method->get('correios_code')));
					break;

				default:
					$type = 'NOT_SPECIFIED';
			}

			$shipping_info = array(
				'type' => $type,
				'cost' => $shipping_price,
				'cep' => $address->get('zip_code'),
				'street' => $address->get('street'),
				'number' => $address->get('number'),
				'complement' => $address->get('complement'),
				'neighborhood' => $address->get('neighborhood'),
				'city' => $address->get('city')->get('name'),
				'state' => $address->get('state')->get('acronym')
			);

			//Realiza o pagamento
			$pagseguro = new Payment\PagSeguro\PagSeguro();
			$payment_url = $pagseguro->checkout($order_id, $products, $buyer_info, $shipping_info, '/checkout/pedido', false);

			//Limpa o carrinho
			$cart->clear();
		}
		catch(Exception $e){
			//Erro ao realizar pedido
			\UI\Message::error($e->getMessage());
			URL\URL::redirect('/checkout?s=1');
		}
	?>
	
	<h2>Você está sendo redirecionado para o PagSeguro para efetuar o pagamento da sua compra com segurança.</h2>

	<img src="/site/media/images/logos/pagseguro.png" class="pagseguro-flag" alt="Pague de forma segura com o PagSeguro" title="Pague de forma segura com o PagSeguro" />
	<span class="loading">Aguarde...</span>
	<p class="redirect-warning"><a href="<?php echo $payment_url ?>">Clique aqui</a> caso você não seja redirecionado automaticamete</p>
</section>

<script>
	//Redireciona para a página de pagamento
	setTimeout(function(){
		window.location.href = '<?php echo $payment_url ?>';
	}, 4000);
</script>