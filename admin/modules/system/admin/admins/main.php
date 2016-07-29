<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Captura os parâmetros
	$id = (int)\HTTP\Request::get('id');
	$mode = strtolower(\HTTP\Request::get('mode'));
	
	//Verifica se o administrador é o principal em modo de edição
	$is_master_edit = (($mode == 'edit') && ($id === \User\Admin::MASTER));
	
	//Campos
	$fields = array(
		'name' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'email' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_email')
		),
		'level_id' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_valid_option')
		),
		'login' => array(
			'save' => true,
			'validation' => array(
				'is_empty',
				'already_exists' => array('table' => 'sys_admin', 'field' => 'login', 'ignore' => array($id)),
				'has_special_chars'
			)
		),
		'password' => array(
			'save' => true,
			'type' => 'password',
			'validation' => array('is_empty')
		),
		'photo' => array(
			'save' => true,
			'validation' => array('is_file')
		),
		'active' => array(
			'save' => true,
			'type' => 'boolean'
		),
		'signup_date' => array(
			'save' => true,
			'type' => 'curdate'
		),
		'signup_time' => array(
			'save' => true,
			'type' => 'curtime'
		)
	);
	
	if($is_master_edit)
		unset($fields['level_id']);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode($mode);
	$form->set_database_options('sys_admin', $fields, $id);
	
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\TextInput('name', $module_language->get('form', 'name')));
	$form->add_field(new \Form\TextInput('email', 'E-mail', '', array(), 'email'));
	$form->add_html('</div>');
	
	if(!$is_master_edit){
		$form->add_field(new \Form\Select('level_id', $module_language->get('form', 'admin_level'), '', array(), \Form\Select::load_options('sys_admin_level', '[name]')));
		$form->add_select_appender('add_admin_level', '#level_id', \User\Admin::MASTER, array(), $module_language->get('form', 'add_admin_level'));
	}
	
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\TextInput('login', 'Login'));
	$form->add_field(new \Form\Password('password', $module_language->get('form', 'password')));
	$form->add_html('</div>');
	
	$form->add_field(new \Form\Image('photo', $module_language->get('form', 'photo'), '', array(), '/uploads/images/admins/'));
	$form->add_field(new \Form\Checkbox('active', $module_language->get('form', 'active'), 1));
	
	$form->add_html('<div class="button-container">');
	$form->add_field(new \Form\Button('submit_button'));
	$form->add_field(new \Form\Button('cancel_button', $sys_language->get('common', 'cancel'), '', array(), 'button'));
	$form->add_html('</div>');
	
	//Valida o formulário
	$form->validate();
	
	//Detecta alterações no formulário
	$form->detect_changes();
	
	//Exibe o formulário
	$form->display();
	
	//Apaga um registro
	$form->delete(true, array('id' => array(\User\Admin::MASTER), 'message' => $module_language->get('form', 'main_admin_message')), false, array('DELETE FROM sys_admin_login_history WHERE admin_id = '.$form->get_record_id()));
	
	//Trata formulário após o envio
	$form->process();
?>