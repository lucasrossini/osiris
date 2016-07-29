<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Lista os registros
	$sql = 'SELECT id, name, price, (CASE WHEN (unit = '.\DAO\Ecommerce\ShippingMethod::PER_PRODUCT.') THEN "'.$module_language->get('form', 'product').'" ELSE "'.$module_language->get('form', 'order').'" END) AS unit, (CASE WHEN active = 1 THEN "'.$sys_language->get('common', '_yes').'" ELSE "'.$sys_language->get('common', '_no').'" END) AS active FROM '.DAO\Ecommerce\ShippingMethod::TABLE_NAME.' WHERE '.search_where_clause(array('name'));
	
	//Tabela
	$columns = array(
		'name' => array('name' => $module_language->get('form', 'name')),
		'price' => array('name' => $module_language->get('form', 'price'), 'type' => 'money'),
		'unit' => array('name' => $module_language->get('form', 'unit')),
		'active' => array('name' => $module_language->get('form', 'active'))
	);
	
	$table = new \Util\Table('records', $sql, $columns);
	$table->sort('name');
	$table->display();
?>

<script>
	//Desabilita a remoção das formas de envio padrão
	$('.delete[rel=1], .delete[rel=2], .delete[rel=3], .delete[rel=4]').addClass('disabled');
</script>