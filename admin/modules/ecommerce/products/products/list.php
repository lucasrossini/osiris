<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Lista os registros
	$sql = 'SELECT p.id, p.name, p.sku, p.date,
			CONCAT("'.DAO\Ecommerce\Product::BASE_PATH.'", p.slug) AS url,
			(CASE WHEN p.visible = 1 THEN "'.$sys_language->get('common', '_yes').'" ELSE "'.$sys_language->get('common', '_no').'" END) AS visible,
			(CASE WHEN p.promotional_price IS NOT NULL THEN p.promotional_price ELSE p.price END) AS price,
			(CASE WHEN (SELECT COUNT(*) FROM ecom_product_variation pv WHERE pv.product_id = p.id) > 0 THEN (SELECT SUM(pv.variation_stock) FROM ecom_product_variation pv WHERE pv.product_id = p.id) ELSE p.stock END) AS stock,
			(SELECT COALESCE(SUM(op.quantity), 0) FROM ecom_order_product op, ecom_order o WHERE op.order_id = o.id AND op.product_id = p.id AND o.status != '.\DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT.') AS sold
			FROM '.DAO\Ecommerce\Product::TABLE_NAME.' p
			WHERE '.search_where_clause(array('p.name', 'p.description', 'p.sku'));
	
	//Tabela
	$columns = array(
		'name' => array('name' => $module_language->get('form', 'name'), 'info' => '[url]'),
		'sku' => array('name' => 'SKU'),
		'price' => array('name' => $module_language->get('form', 'price'), 'type' => 'money'),
		'visible' => array('name' => $module_language->get('form', 'visible')),
		'sold' => array('name' => $module_language->get('list', 'sold')),
		'stock' => array('name' => $module_language->get('tabs', 'stock')),
		'date' => array('name' => $module_language->get('list', 'date'), 'type' => 'date')
	);
	
	$table = new \Util\Table('records', $sql, $columns);
	$table->sort('name');
	$table->display();
?>