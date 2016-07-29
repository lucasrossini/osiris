<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Carrega os tipos de banner
	\Form\Select::display_page_changer('type', $module_language->get('form', 'banner_type'), \Form\Select::load_options(\DAO\BannerType::TABLE_NAME, '[name] ([width]px x [height]px)'));
	
	//Lista os registros
	$sql = 'SELECT b.id, b.name, DATE_FORMAT(b.init_date, "%d/%m/%Y") AS init_date_formatted, DATE_FORMAT(b.end_date, "%d/%m/%Y") AS end_date_formatted, bt.name AS type_name FROM '.\DAO\Banner::TABLE_NAME.' b, '.\DAO\BannerType::TABLE_NAME.' bt WHERE b.type_id = bt.id AND '.filter_where_clause(array('b.type_id' => 'type')).' AND '.search_where_clause(array('b.name'));
	
	//Tabela
	$columns = array(
		'name' => array('name' => $module_language->get('form', 'name')),
		'type_name' => array('name' => $module_language->get('form', 'banner_type')),
		'init_date_formatted' => array('name' => $module_language->get('form', 'init_date')),
		'end_date_formatted' => array('name' => $module_language->get('form', 'end_date'))
	);
	
	$table = new \Util\Table('list_banners', $sql, $columns);
	$table->sort('init_date', \Util\Table::SORT_DESC);
	$table->display();
?>