<form method="post" action="/admin/clients/orders/list">
	<?php
		//Carrega as informações do módulo na língua atual
		$module_language = \HTTP\Request::get('module_language');

		//Carrega o pedido
		$id = (int)\HTTP\Request::get('id');
		$order = new DAO\Ecommerce\Order($id);
		
		if(!$order->get('valid'))
			URL\URL::redirect('/admin/clients/orders/list');

		//Produtos do pedido
		$order_products = $order->get_products();
		$products_table_html = '';
		$items_count = 0;

		foreach($order_products as $order_product){
			$product = $order_product['object'];
			$variation = $order_product['variation'];
			$items_count += $order_product['quantity'];
			$variation_html = $variation ? '<p>'.$variation->get('variation_type')->get('name').' '.$variation->get('variation').'</p>' : '';

			$products_table_html .= '
				<tr>
					<td>
						<a href="/admin/products/products/main?mode=view&id='.$product->get('id').'">'.$product->get('name').'</a>
						'.$variation_html.'
					</td>
					<td>'.$order_product['quantity'].'</td>
					<td>'.\Formatter\Number::money($order_product['price']).'</td>
					<td>'.\Formatter\Number::money($order_product['price'] * $order_product['quantity']).'</td>
				</tr>
			';
		}

		//Exibe os dados do pedido
		echo '
			<div class="inline-labels grid-4">
				<div class="label">
					<span class="label-title">'.$module_language->get('form', 'client').'</span>
					<a href="/admin/clients/clients/main?mode=view&id='.$order->get('client')->get('id').'">'.$order->get('client')->get('name').'</a>
				</div>

				<div class="label">
					<span class="label-title">'.$module_language->get('form', 'datetime').'</span>
					'.$order->get('date')->formatted.', '.$order->get('time')->formatted.'
				</div>

				<div class="label">
					<span class="label-title">'.$module_language->get('form', 'total').'</span>
					'.$order->get('total')->formatted.'
				</div>

				<div class="label">
					<span class="label-title">'.$module_language->get('form', 'status').'</span>
					<span class="status-'.(int)$order->get('status').'">'.DAO\Ecommerce\Order::get_status_name($order->get('status')).'</span>
				</div>
			</div>

			<div class="inline-labels grid-4">
				<div class="label">
					<span class="label-title">'.$module_language->get('form', 'address').'</span>
					'.$order->get('address')->get('addressee').'<br />
					'.$order->get('address')->get('street').', '.$order->get('address')->get('number').($order->get('address')->get('complement') ? ' / '.$order->get('address')->get('complement') : '').'<br />
					'.$order->get('address')->get('neighborhood').' - '.$order->get('address')->get('zip_code').'<br />
					'.$order->get('address')->get('city')->get('name').', '.$order->get('address')->get('state')->get('acronym').'
				</div>

				<div class="label">
					<span class="label-title">'.$module_language->get('form', 'shipping_method').'</span>
					'.$order->get('shipping_method')->get('name').' ('.$order->get('shipping_price')->formatted.')
				</div>

				<div class="label">
					<span class="label-title">'.$module_language->get('form', 'payment_method').'</span>
					'.$order->get('payment_method')->get('name').'
				</div>

				<div class="label">
					<span class="label-title">'.$module_language->get('form', 'gift').'</span>
					'.($order->get('gift') ? $sys_language->get('common', '_yes') : $sys_language->get('common', '_no')).'
				</div>
			</div>

			<div class="label">
				<span class="label-title">'.$module_language->get('form', 'products').' ('.$items_count.')</span>

				<table class="default-table">
					<tr>
						<th>'.$module_language->get('form', 'product').'</th>
						<th>'.$module_language->get('form', 'quantity').'</th>
						<th>'.$module_language->get('form', 'unit_value').'</th>
						<th>'.$module_language->get('form', 'total_value').'</th>
					</tr>

					'.$products_table_html.'
				</table>
			</div>
		';

		//Alterar status
		$order_statuses = array(
			'' => $sys_language->get('common', 'select'),
			DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT => DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT),
			DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH => DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH),
			DAO\Ecommerce\Order::STATUS_DELIVERED => DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_DELIVERED),
			DAO\Ecommerce\Order::STATUS_CANCELLED => DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_CANCELLED)
		);

		echo '
			<div class="inline-labels">
				<div class="label">
					<span class="label-title">'.$module_language->get('form', 'change_status').'</span>
					<select name="status">'.Form\Select::array2options($order_statuses, (int)$order->get('status')).'</select>
				</div>
				
				<div class="label">
					<span class="label-title">'.$module_language->get('form', 'tracking_code').'</span>
					<input name="tracking_code" value="'.$order->get('tracking_code').'" />
				</div>
			</div>
			
			<div class="button-container">
				<button type="submit">'.$sys_language->get('common', 'update').'</button>
				<button type="button" onclick="window.location.href=\'/admin/clients/orders/list\'">'.$module_language->get('form', 'back').'</button>
				<input type="hidden" name="record[]" value="'.$order->get('id').'" />
			</div>
		';
	?>
</form>