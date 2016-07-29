<link rel="stylesheet" href="/app/assets/css/grid.css" />
<link rel="stylesheet" href="/site/ecommerce/assets/css/account.css" />

<?php
	//Cabeçalho da conta
	include 'ecommerce/inc/account-header.php';
	
	//Menu da conta
	include 'ecommerce/inc/account-menu.php';
?>

<section id="orders-list">
	<h1>Meus pedidos</h1>
	
	<?php
		//Carrega os pedidos realizados pelo usuário
		$client_id = $sys_user->get('id');
		$orders = \DAO\Ecommerce\Order::load_all('SELECT id FROM '.\DAO\Ecommerce\Order::TABLE_NAME.' WHERE client_id = '.$client_id.' ORDER BY date DESC, time DESC');
		
		//Carrega os detalhes do pedido selecionado
		if(HTTP\Request::is_set('get', 'id')){
			$current_order_id = HTTP\Request::get('id');
			$current_order = new \DAO\Ecommerce\Order($current_order_id);
			
			if(!$current_order->get('valid')){
				\UI\Message::error('Pedido inválido!');
				URL\URL::redirect('/minha-conta/pedidos');
			}
		}
		
		//Exibe a lista de pedidos
		$html = '';
		
		if($orders['count']){
			$html .= '
				<table class="records orders">
					<tr>
						<th>Código</th>
						<th>Valor total</th>
						<th>Data/Hora</th>
						<th>Situação</th>
						<th>Opções</th>
					</tr>
			';
			
			foreach($orders['results'] as $order){
				$current = ($order->get('id') == $current_order_id) ? 'current' : '';
				
				$html .= '
					<tr class="'.$current.'">
						<td class="code">'.$order->get('code').'</td>
						<td class="total">'.$order->get('total')->formatted.'</td>
						<td class="datetime">'.$order->get('date')->formatted.', '.$order->get('time')->formatted.'</td>
						<td class="status s'.(int)$order->get('status').'">'.\DAO\Ecommerce\Order::get_status_name($order->get('status')).'</td>
						<td class="options"><a href="/minha-conta/pedidos?id='.$order->get('id').'">Ver detalhes</a></td>
					</tr>
				';
				
				if($current){
					//Produtos do pedido
					$order_products = $order->get_products();
					$products_table_html = '';
					$items_count = 0;

					foreach($order_products as $order_product){
						$product = $order_product['object'];
						$variation = $order_product['variation'];
						$items_count += $order_product['quantity'];
						$variation_html = $variation ? '<span class="variation">'.$variation->get('variation_type')->get('name').' '.$variation->get('variation').'</span>' : '';

						$products_table_html .= '
							<tr>
								<td>
									<a href="'.$product->get('url').'" class="image">'.$product->get_img_tag(70, 70).'</a>
									<a href="'.$product->get('url').'" class="name">'.$product->get('name').'</a>
									'.$variation_html.'
								</td>
								<td>'.$order_product['quantity'].'</td>
								<td>'.\Formatter\Number::money($order_product['price']).'</td>
								<td>'.\Formatter\Number::money($order_product['price'] * $order_product['quantity']).'</td>
							</tr>
						';
					}
					
					//Andamento
					$progress_html = '';
					
					for($step = \DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT; $step <= \DAO\Ecommerce\Order::STATUS_DELIVERED; $step++){
						$done = ($order->get('status') >= $step) ? 'done' : '';
						
						switch($step){
							case \DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT: //Pedido realizado
								$progress_html .= '
									<div class="step s'.(int)$step.' '.$done.'">
										<span class="title">Pedido realizado</span>
										<span class="datetime">'.$order->get('date')->formatted.', às '.$order->get('time')->formatted.'</span>
									</div>
								';
								
								break;
							
							case \DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH: //Pagamento confirmado
								$progress_html .= '
									<div class="step s'.$step.' '.$done.'">
										<span class="title">Pagamento confirmado</span>
										<span class="datetime">'.$order->get('payment_date')->formatted.', às '.$order->get('payment_time')->formatted.'</span>
									</div>
								';
								
								break;
							
							case \DAO\Ecommerce\Order::STATUS_DELIVERED: //Em transporte
								$progress_html .= '
									<div class="step s'.$step.' '.$done.'">
										<span class="title">Em transporte</span>
										<span class="datetime">'.$order->get('dispatch_date')->formatted.', às '.$order->get('dispatch_time')->formatted.'</span>
									</div>
								';
								
								break;
						}
					}
					
					//Prazo de entrega e rastreamento
					$shipping_delay_html = '';
					
					if($order->get('status') == \DAO\Ecommerce\Order::STATUS_DELIVERED){
						$track_shipping_link = $order->get('tracking_code') ? '<a href="'.$order->get('tracking_url').'" target="_blank" title="Veja o andamento do seu pedido no site dos Correios" class="track-shipping">Rastreie seu pedido</a>' : '';
						
						$shipping_delay_html = '
							<div class="delay">
								<span class="name">Data prevista para entrega:</span>
								<span class="date">'.$order->get('delivery_date')->formatted.'</span>
								'.$track_shipping_link.'
							</div>
						';
					}
					
					$html .= '
						<tr>
							<td colspan="5" class="details">
								<div class="row grid c3 clearfix">
									<div class="item">
										<span class="name">Endereço de entrega</span>
										'.$order->get('address')->get('addressee').'<br />
										'.$order->get('address')->get('street').', '.$order->get('address')->get('number').($order->get('address')->get('complement') ? ' / '.$order->get('address')->get('complement') : '').'<br />
										'.$order->get('address')->get('neighborhood').' - '.$order->get('address')->get('zip_code').'<br />
										'.$order->get('address')->get('city')->get('name').', '.$order->get('address')->get('state')->get('acronym').'
									</div>
									
									<div class="item">
										<span class="name">Forma de envio</span>
										'.$order->get('shipping_method')->get('name').' ('.$order->get('shipping_price')->formatted.')
									</div>
									
									<div class="item">
										<span class="name">Forma de pagamento</span>
										'.$order->get('payment_method')->get('name').'
									</div>
								</div>
								
								<div class="row">
									<h3>Andamento do pedido</h3>
									<div class="progress">'.$progress_html.'</div>
									'.$shipping_delay_html.'
								</div>
								
								<div class="row">
									<h3>Produtos do pedido ('.$items_count.')</h3>

									<table class="products">
										<tr>
											<th>Produto</th>
											<th>Quantidade</th>
											<th>Valor unitário</th>
											<th>Valor total</th>
										</tr>

										'.$products_table_html.'
									</table>
								</div>
								
								<div class="contact">
									<h4>Dúvidas com pagamento, entrega ou sobre o produto?</h4>
									<p>Entre em contato conosco que nós esclarecemos para você!</p>
									
									<a href="/contato">Fale conosco</a>
								</div>
							</td>
						</tr>
					';
				}
			}
			
			$html .= '</table>';
		}
		else{
			$html .= '
				<p class="empty">
					Você ainda não realizou nenhum pedido!
					<a href="/produtos">Compre agora</a>
				</p>
			';
		}
		
		echo $html;
	?>
</section>