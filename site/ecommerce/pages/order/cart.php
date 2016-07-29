<link rel="stylesheet" href="/site/ecommerce/assets/css/cart.css" />
<link rel="stylesheet" href="/site/ecommerce/assets/css/shipping.css" />

<section id="cart">
	<h1>Carrinho</h1>
	
	<?php
		//Carrega os recursos necessários
		$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.maskedinput.min.js');
		
		//Instancia o carrinho
		$cart = new DAO\Ecommerce\Cart();
		
		//Ações do carrinho
		if(\HTTP\Request::is_set('get', 'action')){
			$action = \HTTP\Request::get('action');

			switch($action){
				case 'add': //Adiciona um produto ao carrinho
					try{
						$product_id = (int)HTTP\Request::get('product');
						$variation_id = HTTP\Request::get('variation');

						if($cart->add($product_id, $variation_id))
							UI\Message::success('Produto adicionado ao carrinho com sucesso!');
						else
							UI\Message::error('Falha ao adicionar produto no carrinho! Tente novamente.');
					}
					catch(Exception $e){
						UI\Message::error($e->getMessage());
					}

					break;

				case 'delete': //Remove um produto do carrinho
					$product_id = (int)HTTP\Request::get('product');
					$variation_id = HTTP\Request::get('variation');

					if($cart->delete($product_id, $variation_id))
						UI\Message::success('Produto removido do carrinho!');
					else
						UI\Message::error('Falha ao remover produto do carrinho! Tente novamente.');

					break;

				case 'update': //Atualiza a quantidade de itens de um produto do carrinho
					try{
						$product_id = (int)HTTP\Request::post('product');
						$variation_id = HTTP\Request::post('variation');
						$quantity = (int)HTTP\Request::post('quantity');

						if($cart->update($product_id, $variation_id, $quantity))
							UI\Message::success('Quantidade de itens do produto alterada com sucesso!');
						else
							UI\Message::error('Falha ao alterar quantidade de itens do produto! Tente novamente.');
					}
					catch(Exception $e){
						UI\Message::error($e->getMessage());
					}

					break;
				
				case 'calculate_shipping': //Calcula o frete
					$zip_code = \HTTP\Request::post('zip_code');
					$cart->calculate_shipping($zip_code);
					
					break;

				case 'select_shipping': //Seleciona um método de envio
					$shipping_method_id = \HTTP\Request::get('method');
					$cart->select_shipping($shipping_method_id);

					break;
				
				case 'clear': //Limpa o carrinho
					$cart->clear();
					break;
			}
			
			URL\URL::redirect('/carrinho');
		}

		//Exibe o carrinho
		$html = '';
		
		if(!$cart->is_empty()){
			//Carrega os produtos do carrinho
			$cart_products = $cart->retrieve();
			
			//Calcula o valor total de produtos
			$cart_total = $cart->calculate_total();
			$cart_shipping = $cart->get_shipping();
			
			$html .= '
				<form method="post" action="/carrinho?action=calculate_shipping" class="shipping-box">
					<h3>Consulte o frete e o prazo de entrega do seu pedido</h3>

					<input type="text" id="zip_code" name="zip_code" value="'.$cart_shipping['zip_code'].'" placeholder="Digite seu CEP" />
					<button type="submit">Calcular</button>

					<a href="http://www.buscacep.correios.com.br" target="_blank" class="find-cep">Não sei meu CEP</a>
			';

			if(sizeof($cart_shipping)){
				$html .= '<div class="methods">';
				
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
				
				$html .= '</div>';
			}

			$html .= '</form>';
			
			//Tabela de produtos
			$html .= '
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
							<a href="'.$product->get('url').'" class="image">'.$product->get_img_tag(100, 100).'</a>
							
							<div class="details">
								<a href="'.$product->get('url').'" class="name">'.$product->get('name').'</a>
								'.$variation_html.'
							</div>
						</td>
						
						<td class="quantity">
							<form method="post" action="/carrinho?action=update">
								<input type="number" name="quantity" value="'.$quantity.'" min="1" max="'.$stock.'" class="spinner" />
								<input type="hidden" name="product" value="'.$product->get('id').'" />
								<input type="hidden" name="variation" value="'.$variation_id.'" />
								<button type="submit">OK</button>
							</form>
							
							<a href="/carrinho?action=delete&product='.$product->get('id').'&variation='.$variation_id.'" class="delete" title="Remover produto">Remover produto</a>
						</td>
						
						<td class="unit-price">'.$product->get('current_price')->formatted.'</td>
						<td class="total-price">'.Formatter\Number::money($product->get('current_price')->original * $quantity).'</td>
					</tr>
				';
			}
			
			if($cart_shipping['selected']){
				$shipping_price = $cart_shipping['methods'][$cart_shipping['selected']]['price'];
				$shipping_method = new DAO\Ecommerce\ShippingMethod($cart_shipping['selected']);
				$shipping_html = ($shipping_method->get('id') != DAO\Ecommerce\ShippingMethod::FREE_SHIPPING_ID) ? Formatter\Number::money($shipping_price).' ('.$shipping_method->get('name').')' : $shipping_method->get('name');
			}
			else{
				$shipping_price = 0;
				$shipping_html = '<a href="#" class="calc">Calcular</a>';
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

				<div class="buttons">
					<a href="/produtos" class="continue">Escolher mais produtos</a>
					<a href="/carrinho?action=clear" class="clear">Limpar carrinho</a>
					<a href="/checkout" class="checkout">Comprar</a>
				</div>
			';
		}
		else{
			$html .= '
				<p class="empty">
					Seu carrinho de compras está vazio!
					<a href="/produtos">Confira nossos produtos</a>
				</p>
			';
		}
		
		echo $html;
	?>
</section>

<script>
	$(document).ready(function(){
		//Frete
		$('#zip_code').mask('99999-999');
		
		$('table.records .shipping .calc').click(function(){
			$('#zip_code').focus();
			return false;
		});
		
		$('input[name="shipping_method_id"]').not(':checked').click(function(){
			window.location.href = '/carrinho?action=select_shipping&method=' + $(this).val();
		});
	});
</script>