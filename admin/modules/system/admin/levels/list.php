<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Lista os registros
	$sql = 'SELECT id, name FROM sys_admin_level WHERE '.search_where_clause(array('name'));
	
	//Tabela
	$columns = array(
		'name' => array('name' => $module_language->get('sidebar', 'level'))
	);
	
	$table = new \Util\Table('records', $sql, $columns);
	$table->sort('name');
	$table->display();
?>

<script>
	//Desabilita a remoção do nível de administração master
	$('.delete[rel=1]').addClass('disabled');
</script>