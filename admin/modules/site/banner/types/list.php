<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Lista os registros
	$sql = 'SELECT id, name, width, height,
			(CASE WHEN is_popup = 1 THEN "'.$sys_language->get('common', '_yes').'" ELSE "'.$sys_language->get('common', '_no').'" END) AS is_popup,
			(CASE WHEN is_rotative = 1 THEN "'.$sys_language->get('common', '_yes').'" ELSE "'.$sys_language->get('common', '_no').'" END) AS is_rotative
			FROM '.\DAO\BannerType::TABLE_NAME.'
			WHERE '.search_where_clause(array('name'));
	
	//Tabela
	$columns = array(
		'name' => array('name' => $module_language->get('form', 'name')),
		'width' => array('name' => $module_language->get('form', 'width')),
		'height' => array('name' => $module_language->get('form', 'height')),
		'is_popup' => array('name' => 'Pop-up'),
		'is_rotative' => array('name' => $module_language->get('form', 'rotative'))
	);
	
	$table = new \Util\Table('list_banner_types', $sql, $columns);
	$table->sort('name');
	$table->display();
?>