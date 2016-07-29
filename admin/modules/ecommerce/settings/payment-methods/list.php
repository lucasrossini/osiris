<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Lista os registros
	$sql = 'SELECT id, name, (CASE WHEN active = 1 THEN "'.$sys_language->get('common', '_yes').'" ELSE "'.$sys_language->get('common', '_no').'" END) AS active FROM '.DAO\Ecommerce\PaymentMethod::TABLE_NAME.' WHERE '.search_where_clause(array('name'));
	
	//Tabela
	$columns = array(
		'name' => array('name' => $module_language->get('form', 'name')),
		'active' => array('name' => $module_language->get('form', 'active'))
	);
	
	$table = new \Util\Table('records', $sql, $columns);
	$table->sort('name');
	$table->display();
?>

<script>
	//Desabilita a remoção das formas de pagamento padrão
	$('.delete[rel=1]').addClass('disabled');
</script>