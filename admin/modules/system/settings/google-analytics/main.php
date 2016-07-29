<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Campos
	$fields = array(
		'ua_id' => array('save' => true),
		'profile_id' => array('save' => true),
		'email' => array(
			'save' => true,
			'validation' => array('is_email')
		),
		'password' => array(
			'save' => true,
			'type' => 'password'
		)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode('edit', false);
	$form->set_database_options('conf_ga', $fields, 1);
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\TextInput('ua_id', $module_language->get('form', 'ua_id'), '', array(), 'text', 0, '99999999-9'), $sys_language->get('common', 'example').' UA-<strong>XXXXXXXX-X</strong>');
	$form->add_field(new \Form\TextInput('profile_id', $module_language->get('form', 'profile_id')), $sys_language->get('common', 'example').' https://www.google.com/analytics/web/?pli=1#report/visitors-overview/aXXXXXXXXwXXXXXXXXp<strong>XXXXXXXX</strong>');
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\TextInput('email', $module_language->get('form', 'email'), '', array(), 'email'));
	$form->add_field(new \Form\Password('password', $module_language->get('form', 'password'), '', array(), true, false));
	$form->add_html('</div>');
	
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