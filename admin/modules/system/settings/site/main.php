<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Campos
	$fields = array(
		'title' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'subtitle' => array(
			'save' => true
		),
		'description' => array(
			'save' => true
		),
		'keywords' => array(
			'save' => true
		),
		'title_separator' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_valid_option')
		),
		'logo' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_file')
		),
		'release_date' => array(
			'save' => true,
			'type' => 'date',
			'validation' => array('is_date')
		)
	);
	
	//Opções de separador de título
	$title_separator_options = array('' => $sys_language->get('common', 'select'), '-' => '-', '»' => '»', '›' => '›', '/' => '/', '•' => '•');
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode('edit', false);
	$form->set_database_options('conf_general', $fields, 1);
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\TextInput('title', $module_language->get('form', 'title')));
	$form->add_field(new \Form\TextInput('subtitle', $module_language->get('form', 'subtitle')));
	$form->add_html('</div>');
	
	$form->add_field(new \Form\Textarea('description', $module_language->get('form', 'description'), '', array('class' => 'short'), 200));
	
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\TextInput('keywords', $module_language->get('form', 'keywords')), '('.$module_language->get('form', 'keywords_obs').')');
	$form->add_field(new \Form\Select('title_separator', $module_language->get('form', 'title_separator'), '', array(), $title_separator_options));
	$form->add_html('</div>');
	
	$form->add_field(new \Form\Upload('logo', $module_language->get('form', 'logo'), array(), array(), '/uploads/images/site/', true, 1, array('png')));
	$form->add_field(new \Form\Date('release_date', $module_language->get('form', 'release_date')), $module_language->get('form', 'release_date_obs'));
	
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