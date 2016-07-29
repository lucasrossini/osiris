<section id="products-list">
	<h1>Todos os produtos</h1>
	
	<?php
		//Ordenação
		switch(HTTP\Request::get('order')){
			case \DAO\Ecommerce\Product::ORDER_ALPHA: //Alfabética
				$sql = 'SELECT id FROM '.DAO\Ecommerce\Product::TABLE_NAME.' WHERE visible = 1 ORDER BY name';
				break;

			case \DAO\Ecommerce\Product::ORDER_PRICE_ASC: //Menor preço
				$sql = 'SELECT id, (CASE WHEN promotional_price IS NOT NULL THEN promotional_price ELSE price END) AS price FROM '.DAO\Ecommerce\Product::TABLE_NAME.' WHERE visible = 1 ORDER BY price';
				break;

			case \DAO\Ecommerce\Product::ORDER_PRICE_DESC: //Maior preço
				$sql = 'SELECT id, (CASE WHEN promotional_price IS NOT NULL THEN promotional_price ELSE price END) AS price FROM '.DAO\Ecommerce\Product::TABLE_NAME.' WHERE visible = 1 ORDER BY price DESC';
				break;

			case \DAO\Ecommerce\Product::ORDER_RECENT: //Mais recentes
				$sql = 'SELECT id FROM '.DAO\Ecommerce\Product::TABLE_NAME.' WHERE visible = 1 ORDER BY date DESC, time DESC';
				break;
			
			case \DAO\Ecommerce\Product::ORDER_MOST_SOLD: //Mais vendidos
				$sql = 'SELECT p.id, (SELECT COALESCE(SUM(op.quantity), 0) FROM ecom_order_product op, ecom_order o WHERE op.order_id = o.id AND op.product_id = p.id AND o.status != '.\DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT.') AS sold_count FROM '.DAO\Ecommerce\Product::TABLE_NAME.' p WHERE p.visible = 1 ORDER BY sold_count DESC';
				break;

			default: //Randômico
				$sql = 'SELECT id FROM '.DAO\Ecommerce\Product::TABLE_NAME.' WHERE visible = 1 ORDER BY RAND('.RANDOM_SEED.')';
		}

		$order_options = \DAO\Ecommerce\Product::get_order_options();
		$order_options_html = '';
		$current_order = \HTTP\Request::get('order');

		foreach($order_options as $order_key => $order_option){
			$current = ($order_key == $current_order) ? 'current' : '';
			$order_options_html .= '<a href="'.URL\URL::add_params(URL, array('order' => $order_key)).'" class="'.$current.'">'.$order_option.'</a>';
		}

		echo '<nav class="order-options">'.$order_options_html.'</nav>';

		//Carrega todos os produtos
		$products = \DAO\Ecommerce\Product::load_all($sql, 0, 9, true);

		//Exibe a lista de produtos
		$html = '<div class="products-list">';

		if($products['count']){
			foreach($products['results'] as $product)
				$html .= $product->get_html();
		}
		else{
			echo '<p class="no-results">Nenhum produto encontrado</p>';
		}

		$html .= '</div>';
		echo $html;
		
		//Paginação
		$products['paginator']->display_pages();
	?>
</section>