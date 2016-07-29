<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Lista os registros
	$sql = 'SELECT c.id, c.name, c.email, c.signup_date, (SELECT COUNT(*) FROM ecom_order o WHERE o.client_id = c.id AND o.status != '.\DAO\Ecommerce\Order::STATUS_AWAITING_PAYMENT.') AS total_orders, (SELECT o.date FROM ecom_order o WHERE o.client_id = c.id ORDER BY o.date DESC LIMIT 0,1) AS last_order_date FROM '.DAO\Ecommerce\Client::TABLE_NAME.' c WHERE '.search_where_clause(array('c.name', 'c.email', 'c.cpf'));
	
	//Tabela
	$columns = array(
		'name' => array('name' => $module_language->get('form', 'name')),
		'email' => array('name' => 'E-mail'),
		'signup_date' => array('name' => $module_language->get('list', 'signup_date'), 'type' => 'date'),
		'total_orders' => array('name' => $module_language->get('list', 'total_orders')),
		'last_order_date' => array('name' => $module_language->get('list', 'last_order_date'), 'type' => 'date')
	);
	
	$table = new \Util\Table('records', $sql, $columns);
	$table->sort('signup_date', Util\Table::SORT_DESC);
	$table->display();
?>