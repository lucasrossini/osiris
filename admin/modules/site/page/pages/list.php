<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Lista os registros
	$sql = 'SELECT id, title, subtitle, CONCAT("/", slug) AS url, (CASE WHEN `show` = 1 THEN "'.$sys_language->get('common', '_yes').'" ELSE "'.$sys_language->get('common', '_no').'" END) AS `show` FROM sys_page WHERE '.search_where_clause(array('title', 'text'));
	
	//Tabela
	$columns = array(
		'title' => array('name' => $module_language->get('form', 'title')),
		'subtitle' => array('name' => $module_language->get('form', 'subtitle')),
		'url' => array('name' => 'URL'),
		'show' => array('name' => $module_language->get('form', 'show'))
	);
	
	$table = new \Util\Table('records', $sql, $columns);
	$table->sort('title');
	$table->display();
?>