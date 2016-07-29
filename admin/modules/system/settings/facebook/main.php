<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Campos
	$fields = array(
		'page_id' => array('save' => true),
		'app_id' => array('save' => true),
		'app_secret' => array('save' => true)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode('edit', false);
	$form->set_database_options('conf_facebook', $fields, 1);
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\TextInput('page_id', $module_language->get('form', 'page_id')));
	$form->add_field(new \Form\TextInput('app_id', $module_language->get('form', 'app_id')));
	$form->add_field(new \Form\TextInput('app_secret', $module_language->get('form', 'app_secret')));
	$form->add_html('</div>');
	
	$form->add_html('<div class="button-container">');
	$form->add_field(new \Form\Button('submit_button'));
	$form->add_html('</div>');
	
	//Detecta alterações no formulário
	$form->detect_changes();
	
	//Exibe o formulário
	$form->display();
	
	//Trata formulário após o envio
	$form->process();
?>