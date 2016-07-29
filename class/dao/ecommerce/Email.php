<?php
	namespace DAO\Ecommerce;
	
	/**
	 * Classe para envio de e-mails da loja.
	 * 
	 * @package Osiris/E-Commerce
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 28/03/2014
	*/
	
	abstract class Email{
		/**
		 * Monta a tabela de produtos do pedido.
		 * 
		 * @param int $order_id ID do pedido.
		 */
		private static function get_order_products_table($order_id){
			$order = new Order($order_id);
			
			$html = '
				<table width="100%" cellpadding="10" summary="Pedido" border="1" style="border-collapse: collapse; border: solid 1px #CCC">
					<thead>
						<tr>
							<th bgcolor="#EEEEEE">Produto</th>
							<th bgcolor="#EEEEEE">Quantidade</th>
							<th bgcolor="#EEEEEE" width="110">Valor unitário</th>
							<th bgcolor="#EEEEEE" width="110">Valor total</th>
						</tr>
					</thead>

					<tbody>
			';
			
			$order_products = $order->get_products();
			$cart_total = 0;

			foreach($order_products as $order_product){
				$product = $order_product['object'];
				$variation = $order_product['variation'];
				$quantity = $order_product['quantity'];

				$variation_html = $variation ? '<br />'.$variation->get('variation_type')->get('name').' '.$variation->get('variation') : '';
				$cart_total += ($quantity * $order_product['price']);

				$html .= '
					<tr>
						<td>
							<a href="'.BASE.$product->get('url').'">'.$product->get('name').'</a>
							'.$variation_html.'
						</td>
						<td align="center">'.$quantity.'</td>
						<td align="right">'.\Formatter\Number::money($order_product['price']).'</td>
						<td align="right">'.\Formatter\Number::money($order_product['price'] * $quantity).'</td>
					</tr>
				';
			}

			$shipping_method = $order->get('shipping_method');
			$shipping_html = ((int)$shipping_method->get('id') !== ShippingMethod::FREE_SHIPPING_ID) ? $order->get('shipping_price')->formatted.' ('.$shipping_method->get('name').')' : $shipping_method->get('name');

			$html .= '
					</tbody>

					<tfoot>
						<tr>
							<th colspan="3" align="right" bgcolor="#EEEEEE">Subtotal</th>
							<td align="right">'.\Formatter\Number::money($cart_total).'</td>
						</tr>

						<tr>
							<th colspan="3" align="right" bgcolor="#EEEEEE">Frete</th>
							<td align="right">'.$shipping_html.'</td>
						</tr>

						<tr>
							<th colspan="3" align="right" bgcolor="#EEEEEE">Total</th>
							<td align="right"><strong style="color: green">'.$order->get('total')->formatted.'</strong></td>
						</tr>
					</tfoot>
				</table>
			';
			
			return $html;
		}
		
		/**
		 * Envia e-mail de confirmação da realização do pedido.
		 * 
		 * @param int $order_id ID do pedido.
		 */
		public static function order_confirmation($order_id){
			//Carrega o pedido
			$order = new Order($order_id);
			$client = $order->get('client');
			
			//Monta a mensagem
			$message = '
				<h1>Confirmação do pedido '.$order->get('code').'</h1>

				<p>
					Seu pedido foi realizado com sucesso e estamos aguardando a confirmação do seu pagamento.<br />
					Você receberá um e-mail informando a confirmação.
				</p>

				<h2>Resumo do seu pedido</h2>
				'.self::get_order_products_table($order_id).'

				<p>Você também pode acompanhar o andamento do seu pedido através da área <a href="'.BASE.'/minha-conta/pedidos">Meus pedidos</a> em sua conta.</p>

				<p>
					Obrigado por comprar conosco!<br />
					<em>'.TITLE.'</em>
				</p>
			';
			
			//Envia o e-mail
			$mail = new \Mail\Email(array('name' => TITLE, 'email' => MAIL_USER), array('name' => $client->get('name'), 'email' => $client->get('email')), 'Confirmação de pedido', $message);
			return $mail->send(false);
		}
		
		/**
		 * Envia e-mail de confirmação do pagamento do pedido.
		 * 
		 * @param int $order_id ID do pedido.
		 */
		public static function payment_confirmation($order_id){
			//Carrega o pedido
			$order = new Order($order_id);
			$client = $order->get('client');
			
			//Monta a mensagem
			$message = '
				<h1>Confirmação de pagamento do pedido '.$order->get('code').'</h1>

				<p>Recebemos a confirmação do pagamento da quantia de <strong style="color: green">'.$order->get('total')->formatted.'</strong> referente ao seu pedido <strong>nº '.$order->get('code').'</strong>.</p>
				<p>Seu pedido está sendo preparado e em breve você receberá um e-mail de confirmação do envio com o número de rastreamento.</p>

				<h2>Resumo do seu pedido</h2>
				'.self::get_order_products_table($order_id).'

				<p>Você também pode acompanhar o andamento do seu pedido através da área <a href="'.BASE.'/minha-conta/pedidos">Meus pedidos</a> em sua conta.</p>

				<p>
					Obrigado por comprar conosco!<br />
					<em>'.TITLE.'</em>
				</p>
			';
			
			//Envia o e-mail
			$mail = new \Mail\Email(array('name' => TITLE, 'email' => MAIL_USER), array('name' => $client->get('name'), 'email' => $client->get('email')), 'Confirmação de pagamento', $message);
			return $mail->send(false);
		}
		
		/**
		 * Envia e-mail de confirmação do envio do pedido.
		 * 
		 * @param int $order_id ID do pedido.
		 */
		public static function dispatch_confirmation($order_id){
			//Carrega o pedido
			$order = new Order($order_id);
			$client = $order->get('client');
			
			//Monta a mensagem
			$tracking_code_phrase = $order->get('tracking_code') ? '<br />Código de rastreamento da sua encomenda: <a href="'.$order->get('tracking_url').'">'.$order->get('tracking_code').'</a>' : '';
			
			$message = '
				<h1>Confirmação de envio do pedido '.$order->get('code').'</h1>

				<p>Seu pedido foi preparado e enviado para o seguinte endereço:</p>
				
				<address>
					'.$order->get('address')->get('addressee').'<br />
					'.$order->get('address')->get('street').', '.$order->get('address')->get('number').($order->get('address')->get('complement') ? ' / '.$order->get('address')->get('complement') : '').'<br />
					'.$order->get('address')->get('neighborhood').' - '.$order->get('address')->get('zip_code').'<br />
					'.$order->get('address')->get('city')->get('name').', '.$order->get('address')->get('state')->get('acronym').'
				</address>
				
				<p>O prazo de entrega estimado para o seu pedido é de <strong>'.\Formatter\String::count($order->get('delivery_days'), 'dia útil', 'dias úteis').'</strong>.'.$tracking_code_phrase.'</p>

				<h2>Resumo do seu pedido</h2>
				'.self::get_order_products_table($order_id).'

				<p>Você também pode acompanhar o andamento do seu pedido através da área <a href="'.BASE.'/minha-conta/pedidos">Meus pedidos</a> em sua conta.</p>

				<p>
					Obrigado por comprar conosco!<br />
					<em>'.TITLE.'</em>
				</p>
			';
			
			//Envia o e-mail
			$mail = new \Mail\Email(array('name' => TITLE, 'email' => MAIL_USER), array('name' => $client->get('name'), 'email' => $client->get('email')), 'Pedido enviado', $message);
			return $mail->send(false);
		}
	}
?>