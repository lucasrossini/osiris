<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Campos
	$fields = array(
		'url_web' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_url')
		),
		'url_local' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_url')
		),
		'url_server' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_url')
		),
		'use_www' => array(
			'save' => true,
			'type' => 'int'
		)
	);
	
	//Opções de uso de 'www'
	$use_www_options = array(0 => $module_language->get('use_www', 'default'), 1 => $module_language->get('use_www', 'ever'), 2 => $module_language->get('use_www', 'never'));
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode('edit', false);
	$form->set_database_options('conf_server', $fields, 1);
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\TextInput('url_web', $module_language->get('form', 'url_web'), '', array(), 'url'));
	$form->add_field(new \Form\TextInput('url_local', $module_language->get('form', 'url_local'), '', array(), 'url'));
	$form->add_field(new \Form\TextInput('url_server', $module_language->get('form', 'url_server'), '', array(), 'url'));
	$form->add_html('</div>');
	
	$form->add_field(new \Form\RadioGroup('use_www', $module_language->get('form', 'use_www'), '', array(), $use_www_options));
	
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