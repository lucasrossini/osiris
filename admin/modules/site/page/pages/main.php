<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Captura os parâmetros
	$id = (int)\HTTP\Request::get('id');
	$mode = strtolower(\HTTP\Request::get('mode'));
	
	//Campos
	$fields = array(
		'title' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'subtitle' => array(
			'save' => true
		),
		'slug' => array(
			'save' => true,
			'type' => 'slug[title]'
		),
		'custom_slug' => array(
			'save' => true
		),
		'text' => array(
			'save' => true,
			'type' => 'editor',
			'validation' => array('is_empty')
		),
		'description' => array(
			'save' => true
		),
		'keywords' => array(
			'save' => true
		),
		'show' => array(
			'save' => true,
			'type' => 'boolean'
		),
		'is_faq' => array(
			'save' => true,
			'type' => 'boolean'
		)
	);
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode($mode);
	$form->set_database_options('sys_page', $fields, $id, array(), '\DAO\Page');
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels">');
	$form->add_field(new \Form\TextInput('title', $module_language->get('form', 'title')));
	$form->add_field(new \Form\TextInput('subtitle', $module_language->get('form', 'subtitle')));
	$form->add_html('</div>');
	
	$form->add_field(new \Form\TextInput('custom_slug', $module_language->get('form', 'custom_slug')), sprintf($module_language->get('form', 'custom_slug_obs'), '<em><a href="http://en.wikipedia.org/wiki/Slug_(web_publishing)" target="_blank">slug</a></em>'));
	$form->add_field(new \Form\Editor('text', $module_language->get('form', 'text')));
	$form->add_field(new \Form\Textarea('description', $module_language->get('form', 'description'), '', array('class' => 'short'), 200));
	$form->add_field(new \Form\TextInput('keywords', $module_language->get('form', 'keywords')), $module_language->get('form', 'keywords_obs'));
	$form->add_field(new \Form\Checkbox('show', $module_language->get('form', 'show'), 1));
	$form->add_field(new \Form\Checkbox('is_faq', $module_language->get('form', 'is_faq'), 1));
	
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
	$form->delete();
	
	//Trata formulário após o envio
	$after_queries = $form->get('custom_slug') ? array('UPDATE sys_page SET slug = "'.ltrim($form->get('custom_slug'), '/').'" WHERE id = %pid') : array();
	$form->process(true, $after_queries);
?>