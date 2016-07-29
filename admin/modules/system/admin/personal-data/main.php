<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
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
		'login' => array(
			'save' => true,
			'validation' => array(
				'is_empty',
				'already_exists' => array('table' => 'sys_admin', 'field' => 'login', 'ignore' => array($sys_user->get('id'))),
				'has_special_chars'
			)
		),
		'photo' => array(
			'save' => true,
			'validation' => array('is_file')
		)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode('edit', false);
	$form->set_database_options('sys_admin', $fields, $sys_user->get('id'));
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\TextInput('name', $module_language->get('form', 'name')));
	$form->add_field(new \Form\TextInput('email', 'E-mail', '', array(), 'email'));
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\TextInput('login', 'Login'));
	$form->add_field(new \Form\Password('password', $module_language->get('form', 'password')));
	$form->add_html('</div>');
	
	$form->add_field(new \Form\Image('photo', $module_language->get('form', 'photo'), '', array(), '/uploads/images/admins/'));
	
	$form->add_html('<div class="button-container">');
	$form->add_field(new \Form\Button('submit_button'));
	$form->add_html('</div>');
	
	//Valida o formulário
	$form->validate();
	
	//Detecta alterações no formulário
	$form->detect_changes();
	
	//Exibe o formulário
	$form->display();
	
	//Trata formulário após o envio
	$form->process();
?>