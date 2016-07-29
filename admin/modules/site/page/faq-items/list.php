<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Carrega as páginas de FAQ
	$faq_page_options = \Form\Select::load_options('sys_page', '[title]', 'is_faq = 1');
	\Form\Select::display_page_changer('faq', $module_language->get('form', 'faq_page'), $faq_page_options);
	
	//Lista os registros
	$sql = 'SELECT i.id, i.question, i.answer FROM sys_faq_item i, sys_page p WHERE i.page_id = p.id AND '.filter_where_clause(array('i.page_id' => 'faq')).' AND '.search_where_clause(array('i.question', 'i.answer'));
	
	//Tabela
	$columns = array(
		'question' => array('name' => $module_language->get('form', 'question'), 'type' => 'excerpt[150]'),
		'answer' => array('name' => $module_language->get('form', 'answer'), 'type' => 'excerpt[150]')
	);
	
	$table = new \Util\Table('records', $sql, $columns);
	$table->sort('question');
	$table->display();
?>