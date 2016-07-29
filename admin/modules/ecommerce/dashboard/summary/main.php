<link href="app/assets/css/grid.css" rel="stylesheet" />
<link href="admin/modules/system/stats/stats/assets/styles.css" rel="stylesheet" />

<style>
	.google-chart{ height: 300px; }
</style>

<?php
	//Script do Google Chart
	echo \Google\GoogleChart::get_script();
	
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Total de produtos cadastrados na loja
	$db->query('SELECT COUNT(*) AS total FROM ecom_product');
	$products_count = $db->result(0)->total;
	
	//Total de pedidos realizados no último mês
	$db->query('SELECT COUNT(*) AS total FROM ecom_order WHERE date BETWEEN DATE_FORMAT(NOW() - INTERVAL 1 MONTH, "%Y-%m-%d 00:00:00") AND DATE_FORMAT(NOW(), "%Y-%m-%d 23:59:59")');
	$month_orders_count = $db->result(0)->total;
	
	//Total de itens vendidos no último mês
	$db->query('SELECT COUNT(*) AS total FROM ecom_order o JOIN ecom_order_product op ON (o.id = op.order_id) WHERE o.date BETWEEN DATE_FORMAT(NOW() - INTERVAL 1 MONTH, "%Y-%m-%d 00:00:00") AND DATE_FORMAT(NOW(), "%Y-%m-%d 23:59:59")');
	$month_items_count = $db->result(0)->total;
	
	//Faturamento no último mês
	$db->query('SELECT SUM(total) AS total FROM ecom_order WHERE status = '.DAO\Ecommerce\Order::STATUS_DELIVERED.' AND date BETWEEN DATE_FORMAT(NOW() - INTERVAL 1 MONTH, "%Y-%m-%d 00:00:00") AND DATE_FORMAT(NOW(), "%Y-%m-%d 23:59:59")');
	$month_billing = $db->result(0)->total;
	
	//Últimos pedidos realizados
	$sql = 'SELECT o.id, CONCAT(o.date, " ", o.time) AS datetime, o.total, c.name AS client,
			(CASE
				WHEN (o.status = '.DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT.') THEN
					"<span class=\'status-0\'>'.DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT).'</span>"
				ELSE
					CASE WHEN (o.status = '.DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH.') THEN
						"<span class=\'status-1\'>'.DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_AWAITING_DISPATCH).'</span>"
					ELSE
						CASE WHEN (o.status = '.DAO\Ecommerce\Order::STATUS_DELIVERED.') THEN
							"<span class=\'status-2\'>'.DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_DELIVERED).'</span>"
						ELSE
							CASE WHEN (o.status = '.DAO\Ecommerce\Order::STATUS_CANCELLED.') THEN
								"<span class=\'status-3\'>'.DAO\Ecommerce\Order::get_status_name(DAO\Ecommerce\Order::STATUS_CANCELLED).'</span>"
							ELSE
								""
							END
						END
					END
				END) AS status,
			(SELECT SUM(op.quantity) FROM ecom_order_product op WHERE op.order_id = o.id) AS items_count
			FROM '.DAO\Ecommerce\Order::TABLE_NAME.' o, '.\DAO\Ecommerce\Client::TABLE_NAME.' c
			WHERE o.client_id = c.id';
	
	$columns = array(
		'id' => array('name' => $module_language->get('order', 'code')),
		'client' => array('name' => $module_language->get('order', 'client')),
		'items_count' => array('name' => $module_language->get('order', 'items_count')),
		'total' => array('name' => $module_language->get('order', 'total'), 'type' => 'money'),
		'datetime' => array('name' => $module_language->get('order', 'datetime'), 'type' => 'date'),
		'status' => array('name' => $module_language->get('order', 'status'))
	);

	$table = new \Util\Table('last-orders', $sql, $columns, 5, false, true, false, false);
	$table->sort('datetime', Util\Table::SORT_DESC);
	$last_orders_table = $table->display(false, false, false);
	
	//Produtos mais visualizados
	$db->query('SELECT * FROM (SELECT p.id, p.name, (SELECT COUNT(*) FROM ecom_product_view pv WHERE pv.product_id = p.id) AS total_views FROM ecom_product p) AS sub WHERE total_views > 0 ORDER BY total_views DESC LIMIT 0,5');
	$most_viewed_products = $db->result();
	$most_viewed_products_array = array();
	
	foreach($most_viewed_products as $product){
		$most_viewed_products_array[] = '
			<a href="admin/products/products/main?mode=view&id='.$product->id.'">'.$product->name.'</a>
			<span class="info">'.Formatter\String::count($product->total_views, $module_language->get('form', 'visit'), $module_language->get('form', 'visits')).'</span>
		';
	}
	
	$most_viewed_products_list = sizeof($most_viewed_products_array) ? \Util\ArrayUtil::listify($most_viewed_products_array, 1, true) : '<p class="empty">Nenhum produto</p>';
	
	//Produtos mais vendidos
	$db->query('SELECT * FROM (SELECT p.id, p.name, (SELECT SUM(op.quantity) FROM ecom_order_product op, ecom_order o WHERE op.order_id = o.id AND op.product_id = p.id) AS total_orders FROM ecom_product p) AS sub WHERE total_orders > 0 ORDER BY total_orders DESC LIMIT 0,5');
	$most_sold_products = $db->result();
	$most_sold_products_array = array();
	
	foreach($most_sold_products as $product){
		$most_sold_products_array[] = '
			<a href="admin/products/products/main?mode=view&id='.$product->id.'">'.$product->name.'</a>
			<span class="info">'.Formatter\String::count($product->total_orders, $module_language->get('form', 'sold'), $module_language->get('form', 'sold_plural')).'</span>
		';
	}
	
	$most_sold_products_list = sizeof($most_sold_products_array) ? \Util\ArrayUtil::listify($most_sold_products_array, 1, true) : '<p class="empty">Nenhum produto</p>';
	
	//Produtos com estoque baixo
	define('LOW_STOCK', 5);
	
	$db->query('SELECT * FROM (SELECT p.id, p.name, (CASE WHEN (SELECT COUNT(*) FROM ecom_product_variation pv WHERE pv.product_id = p.id) > 0 THEN (SELECT SUM(pv.variation_stock) FROM ecom_product_variation pv WHERE pv.product_id = p.id) ELSE p.stock END) AS stock FROM ecom_product p) AS sub WHERE stock < '.LOW_STOCK.' ORDER BY stock LIMIT 0,5');
	$low_stock_products = $db->result();
	$low_stock_products_array = array();
	
	foreach($low_stock_products as $product){
		$low_stock_products_array[] = '
			<a href="admin/products/products/main?mode=view&id='.$product->id.'">'.$product->name.'</a>
			<span class="info">'.$product->stock.' '.$module_language->get('form', 'in_stock').'</span>
		';
	}
	
	$low_stock_products_list = sizeof($low_stock_products_array) ? \Util\ArrayUtil::listify($low_stock_products_array) : '<p class="empty">Nenhum produto</p>';
	
	//Gráfico de vendas
	define('CHART_DAYS_INTERVAL', 7);
	$first_date = DateTime\Date::subtract(date('d/m/Y'), CHART_DAYS_INTERVAL);
	
	$sales_count_chart_data = $sales_billing_chart_data = array('date' => array(), 'sales' => array());
	
	$sales_count_chart_columns = $sales_billing_chart_columns = array(
		'date' => array('label' => 'Data', 'type' => 'string'),
		'sales' => array('label' => 'Vendas', 'type' => 'number')
	);
	
	$sales_billing_chart_columns['sales']['type'] = 'currency';
	
	for($day = 1; $day <= CHART_DAYS_INTERVAL; $day++){
		$date = DateTime\Date::add($first_date, $day);
		$db->query('SELECT COUNT(*) AS count_total, SUM(total) AS billing_total FROM ecom_order WHERE date = "'.DateTime\Date::convert($date).'" AND status = '.DAO\Ecommerce\Order::STATUS_DELIVERED);
		$total_orders = (int)$db->result(0)->count_total;
		$total_billing = (float)$db->result(0)->billing_total;
		
		$date_pieces = explode('/', $date);
		array_pop($date_pieces);
		$sales_count_chart_data['date'][] = $sales_billing_chart_data['date'][] = implode('/', $date_pieces);
		$sales_count_chart_data['sales'][] = $total_orders;
		$sales_billing_chart_data['sales'][] = $total_billing;
	}
	
	$sales_count_chart = new \Google\GoogleChart('sales_count_chart', '', 'LineChart', $sales_count_chart_columns, $sales_count_chart_data);
	$sales_billing_chart = new \Google\GoogleChart('sales_billing_chart', '', 'LineChart', $sales_billing_chart_columns, $sales_billing_chart_data);
	
	//Gráfico de produtos por categoria
	$categories_chart_data = array('category' => array(), 'products' => array());
	$db->query('SELECT c.name, (SELECT COUNT(*) FROM ecom_product_category pc WHERE pc.category_id = c.id) AS total_products FROM ecom_category c WHERE c.parent_id IS NULL');
	$categories = $db->result();
	
	$categories_chart_columns = array(
		'category' => array('label' => 'Categoria', 'type' => 'string'),
		'products' => array('label' => 'Produtos', 'type' => 'number')
	);
	
	foreach($categories as $category){
		$categories_chart_data['category'][] = $category->name;
		$categories_chart_data['products'][] = (int)$category->total_products;
	}
	
	$categories_chart = new \Google\GoogleChart('categories_chart', '', 'PieChart', $categories_chart_columns, $categories_chart_data);
	
	//Gráfico de novos cadastros
	$signup_chart_data = array('date' => array(), 'clients' => array());
	
	for($day = 1; $day <= CHART_DAYS_INTERVAL; $day++){
		$date = DateTime\Date::add($first_date, $day);
		$db->query('SELECT COUNT(*) AS total FROM ecom_client WHERE signup_date = "'.DateTime\Date::convert($date).'"');
		$total = (int)$db->result(0)->total;
		
		$date_pieces = explode('/', $date);
		array_pop($date_pieces);
		$signup_chart_data['date'][] = implode('/', $date_pieces);
		$signup_chart_data['clients'][] = $total;
	}
	
	$signup_chart_columns = array(
		'date' => array('label' => 'Data', 'type' => 'string'),
		'clients' => array('label' => 'Cadastros', 'type' => 'number')
	);
	
	$signup_chart = new \Google\GoogleChart('signup_chart', '', 'ColumnChart', $signup_chart_columns, $signup_chart_data);
	
	//Exibe o resumo
	$html = '
		<div class="stats-box">
			<div class="grid c4 clearfix">
				<div class="item">
					<h3>Produtos cadastrados</h3>
					<span class="value">
						'.$products_count.'
						<span class="info">no total</span>
					</span>
				</div>
				
				<div class="item">
					<h3>Pedidos realizados</h3>
					<span class="value">
						'.$month_orders_count.'
						<span class="info">no último mês</span>
					</span>
				</div>
				
				<div class="item">
					<h3>Itens vendidos</h3>
					<span class="value">
						'.$month_items_count.'
						<span class="info">no último mês</span>
					</span>
				</div>
				
				<div class="item">
					<h3>Faturamento</h3>
					<span class="value">
						'.\Formatter\Number::money($month_billing).'
						<span class="info">no último mês</span>
					</span>
				</div>
			</div>
		</div>
		
		<div class="stats-box">
			<h3>
				Últimos pedidos realizados
				<a href="admin/clients/orders/list" class="more">Ver todos</a>
			</h3>
			
			'.$last_orders_table.'
		</div>
		
		<div class="stats-box">
			<div class="grid c3 clearfix">
				<div class="item">
					<h3>Produtos mais visualizados</h3>
					'.$most_viewed_products_list.'
				</div>
				
				<div class="item">
					<h3>Produtos mais vendidos</h3>
					'.$most_sold_products_list.'
				</div>
				
				<div class="item">
					<h3>Produtos com estoque baixo (< '.LOW_STOCK.')</h3>
					'.$low_stock_products_list.'
				</div>
			</div>
		</div>
		
		<div class="stats-box">
			<div class="grid c2 clearfix">
				<div class="item">
					<h3>Gráfico de vendas (quantidade)</h3>
					'.$sales_count_chart->draw(false).'
				</div>
				
				<div class="item">
					<h3>Gráfico de vendas (faturamento)</h3>
					'.$sales_billing_chart->draw(false).'
				</div>
			</div>
		</div>
		
		<div class="stats-box">
			<div class="grid c2 clearfix">
				<div class="item">
					<h3>Gráfico de produtos por categoria</h3>
					'.$categories_chart->draw(false).'
				</div>
				
				<div class="item">
					<h3>Gráfico de cadastros</h3>
					'.$signup_chart->draw(false).'
				</div>
			</div>
		</div>
	';
	
	echo $html;
?>

<script>
	$(document).ready(function(){
		//Corrige os links de visualização dos últimos pedidos
		$('#last-orders .action > .view').each(function(){
			$(this).attr('href', 'admin/clients/orders/main?mode=view&id=' + $(this).attr('rel'));
		});
		
		//Tipsy
		$('.default-table .action a:not(.disabled)').tipsy({gravity: 's', offset: 5});
	});
</script>