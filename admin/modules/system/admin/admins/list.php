<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Carrega os níveis de administração
	$admin_level_options = \Form\Select::load_options('sys_admin_level', '[name]');
	\Form\Select::display_page_changer('level', $module_language->get('form', 'admin_level'), $admin_level_options);
	
	//Lista os registros
	$sql = 'SELECT a.id, a.name, a.email, a.login, l.name AS level FROM sys_admin a, sys_admin_level l WHERE a.level_id = l.id AND '.filter_where_clause(array('a.level_id' => 'level')).' AND '.search_where_clause(array('a.name', 'a.email'));
	
	//Tabela
	$columns = array(
		'name' => array('name' => $module_language->get('form', 'name')),
		'level' => array('name' => $module_language->get('form', 'admin_level')),
		'login' => array('name' => 'Login'),
		'email' => array('name' => 'E-mail')
	);
	
	$table = new \Util\Table('records', $sql, $columns);
	$table->sort('name');
	$table->display();
?>

<script>
	//Desabilita a remoção do administrador master
	$('.delete[rel=1]').addClass('disabled');
</script>