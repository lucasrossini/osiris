<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Lista os registros
	$sql = 'SELECT id, name FROM sys_user WHERE '.search_where_clause(array('name'));
	
	//Tabela
	$columns = array(
		'name' => array('name' => $module_language->get('form', 'name'))
	);
	
	$table = new \Util\Table('records', $sql, $columns);
	$table->sort('name');
	$table->display();
?>