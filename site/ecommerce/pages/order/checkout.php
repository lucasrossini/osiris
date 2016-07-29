<link rel="stylesheet" href="/site/ecommerce/assets/css/cart.css" />
<link rel="stylesheet" href="/site/ecommerce/assets/css/addresses.css" />
<link rel="stylesheet" href="/site/ecommerce/assets/css/shipping.css" />

<section id="checkout">
	<h1>Finalizar compra</h1>
	
	<div class="summary">
		<h2>Resumo do seu pedido</h2>
		<a href="/carrinho" class="back-to-cart">Voltar ao carrinho</a>
		
		<?php
			$client_id = $sys_user->get('id');
			
			//Instancia o carrinho
			$cart = new DAO\Ecommerce\Cart();

			//Redireciona para a página de carrinho se não houver produtos
			if($cart->is_empty())
				URL\URL::redirect('/carrinho');
			
			//Ações de checkout
			if(\HTTP\Request::is_set('get', 'action')){
				$action = \HTTP\Request::get('action');

				switch($action){
					case 'select_address': //Seleciona o endereço
						$address_id = \HTTP\Request::get('address');
						
						$db->query('SELECT zip_code FROM '.\DAO\Ecommerce\Address::TABLE_NAME.' WHERE id = '.$address_id);
						$zip_code = $db->result(0)->zip_code;
						
						$cart->calculate_shipping($zip_code);
						$cart->select_address($address_id);

						break;

					case 'select_shipping': //Seleciona um método de envio
						$shipping_method_id = \HTTP\Request::get('method');
						$cart->select_shipping($shipping_method_id);

						break;
				}

				URL\URL::redirect('/checkout?s=1');
			}

			//Carrega os produtos do carrinho
			$cart_products = $cart->retrieve();
			
			//Calcula o valor total de produtos
			$cart_total = $cart->calculate_total();
			$cart_shipping = $cart->get_shipping();

			//Exibe o carrinho
			$html = '
				<table class="records cart-products">
					<tr>
						<th>Produto</th>
						<th>Quantidade</th>
						<th>Valor unitário</th>
						<th>Valor total</th>
					</tr>
			';
			
			foreach($cart_products as $cart_product){
				$product = $cart_product['product'];
				$product_variation = $cart_product['variation'];
				$quantity = $cart_product['quantity'];

				if($product_variation){
					$variation_id = $product_variation->get('id');
					$stock = $product_variation->get('variation_stock');

					$variation_html = '
						<div class="variation">
							<span class="name">'.$product_variation->get('variation_type')->get('name').'</span>
							<span class="option">'.$product_variation->get('variation').'</span>
						</div>
					';
				}
				else{
					$variation_id = $variation_html = '';
					$stock = $product->get('stock');
				}

				$html .= '
					<tr>
						<td class="product">
							<div class="image">'.$product->get_img_tag(100, 100).'</div>
							
							<div class="details">
								<span class="name">'.$product->get('name').'</span>
								'.$variation_html.'
							</div>
						</td>

						<td class="quantity">'.$quantity.'</td>

						<td class="unit-price">'.$product->get('current_price')->formatted.'</td>
						<td class="total-price">'.Formatter\Number::money($product->get('current_price')->original * $quantity).'</td>
					</tr>
				';
			}
			
			if($cart_shipping['selected']){
				$shipping_price = $cart_shipping['methods'][$cart_shipping['selected']]['price'];
				$shipping_method = new DAO\Ecommerce\ShippingMethod($cart_shipping['selected']);
				$shipping_html = ((int)$shipping_method->get('id') !== DAO\Ecommerce\ShippingMethod::FREE_SHIPPING_ID) ? Formatter\Number::money($shipping_price).' ('.$shipping_method->get('name').')' : $shipping_method->get('name');
			}
			else{
				$shipping_price = 0;
				$shipping_html = '<a href="#" class="select">Selecione um método</a>';
			}

			$html .= '
					<tr class="subtotal">
						<th colspan="3">Subtotal</th>
						<td>'.Formatter\Number::money($cart_total).'</td>
					</tr>

					<tr class="shipping">
						<th colspan="3">Frete</th>
						<td>'.$shipping_html.'</td>
					</tr>

					<tr class="total">
						<th colspan="3">Total</th>
						<td>'.Formatter\Number::money($cart_total + $shipping_price).'</td>
					</tr>
				</table>
			';

			echo $html;
		?>
	</div>

	<div class="shipping-address">
		<h2>Selecione um endereço para entrega</h2>
		
		<div class="address-list">
			<?php
				//Carrega os endereços do usuário
				$addresses = \DAO\Ecommerce\Address::load_all('SELECT id FROM '.DAO\Ecommerce\Address::TABLE_NAME.' WHERE client_id = '.$client_id.' ORDER BY `default` DESC, title');

				//Exibe os endereços
				$html = '';

				//Carrega o endereço marcado
				if(HTTP\Request::is_set('get', 'address')){
					$selected_address = HTTP\Request::get('address');
				}
				elseif(isset($cart_shipping['address'])){
					$selected_address = $cart_shipping['address'];
				}
				else{
					$db->query('SELECT id FROM '.\DAO\Ecommerce\Address::TABLE_NAME.' WHERE `default` = 1 AND client_id = '.$client_id);
					$selected_address = $db->result(0)->id;
				}

				if(!HTTP\Request::get('s'))
					\URL\URL::redirect('/checkout?action=select_address&address='.$selected_address);

				foreach($addresses['results'] as $address){
					$checked = ((int)$selected_address === (int)$address->get('id')) ? 'checked' : '';
					$complement = $address->get('complement') ? ' / '.$address->get('complement') : '';

					$html .= '
						<label>
							<address>
								<input type="radio" name="address_id" value="'.$address->get('id').'" '.$checked.' />
								<h3>'.$address->get('title').'</h3>

								<p>'.$address->get('addressee').'</p>
								<p>'.$address->get('street').', '.$address->get('number').$complement.'</p>
								<p>'.$address->get('neighborhood').' - '.$address->get('zip_code').'</p>
								<p>'.$address->get('city')->get('name').', '.$address->get('state')->get('acronym').'</p>
							</address>
						</label>
					';
				}

				$html .= '
					<address class="new">
						<a href="/minha-conta/enderecos/formulario?checkout=1">
							<span class="plus">+</span>
							Cadastrar novo endereço
						</a>
					</address>
				';

				echo $html;
			?>
		</div>
	</div>
	
	<div class="shipping-box" id="shipping">
		<h2>Selecione um método de envio</h2>
		
		<div class="methods">
			<?php
				$html = '';

				if(sizeof($cart_shipping)){
					foreach($cart_shipping['methods'] as $shipping_method_id => $shipping_attr){
						$checked = ($shipping_method_id == $cart_shipping['selected']) ? 'checked' : '';
						$price_html = ($shipping_method_id != DAO\Ecommerce\ShippingMethod::FREE_SHIPPING_ID) ? '<span class="price">'.Formatter\Number::money($shipping_attr['price']).'</span>' : '';

						$html .= '
							<label class="method">
								<input type="radio" name="shipping_method_id" value="'.$shipping_method_id.'" '.$checked.' />
								<span class="name">'.$shipping_attr['name'].'</span>
								'.$price_html.'
								<span class="delivery">'.Formatter\String::count($shipping_attr['delivery_days'], 'dia útil', 'dias úteis').'</span>
							</label>
						';
					}
				}

				echo $html;
			?>
		</div>
	</div>
	
	<form method="post" action="/checkout/pagamento" id="pay">
		<label class="gift">
			<input type="checkbox" name="gift" value="1" />
			Embalar para presente
			
			<?php
				//Valor da embalagem
				$settings = new DAO\Ecommerce\Settings(1);
				
				if($settings->get('gift_price')->original > 0)
					echo '<span class="price">('.$settings->get('gift_price')->formatted.')</span>';
			?>
		</label>
		
		<button type="submit" class="buy">Finalizar compra</button>
		<p class="info">Ao clicar em <strong>Finalizar compra</strong> você será redirecionado para o <a href="https://pagseguro.uol.com.br" target="_blank">PagSeguro</a>, onde realizará seu pagamento com segurança.</p>
	</form>
</section>

<script>
	$(document).ready(function(){
		//Frete
		$('input[name="address_id"]').not(':checked').click(function(){
			window.location.href = '/checkout?action=select_address&address=' + $(this).val();
		});
		
		$('input[name="shipping_method_id"]').not(':checked').click(function(){
			window.location.href = '/checkout?action=select_shipping&method=' + $(this).val();
		});
		
		$('table.records .shipping .select').click(function(){
			window.location.hash = 'shipping';
			return false;
		});
		
		//Embalar para presente
		$('input[name="gift"]').click(function(){
			var total = <?php echo $cart_total + $shipping_price ?>;
			var gift_price = <?php echo $settings->get('gift_price')->original ?>;
			
			if($(this).is(':checked'))
				total += gift_price;
			
			$('tr.total > td').text('R$ ' + total.toFixed(2).replace('.', ','));
		});
		
		//Finalizar compra
		$('#pay').submit(function(){
			if(!$('input[name="shipping_method_id"]:checked').length){
				alert('Por favor, selecione um método de envio antes de finalizar seu pedido!');
				return false;
			}
			
			$(this).find('button[type="submit"]').attr('disabled', true);
		});
	});
</script>