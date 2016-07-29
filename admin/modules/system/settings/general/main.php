<?php
	//Carrega as informações do módulo na língua atual
	$module_language = \HTTP\Request::get('module_language');
	
	//Campos
	$fields = array(
		'theme' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_valid_option')
		),
		'language' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_valid_option')
		),
		'key' => array(
			'save' => true,
			'validation' => array('is_empty')
		),
		'session_expire' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_valid_option')
		),
		'cookie_expire' => array(
			'save' => true,
			'validation' => array('is_empty', 'is_valid_option')
		),
		'max_image_size' => array(
			'save' => true,
			'type' => 'int',
			'validation' => array('is_empty', 'is_number')
		),
		'display_errors' => array(
			'save' => true,
			'type' => 'boolean'
		)
	);
	
	//Opções de tempo de duração de sessão
	$session_expire_options = array(
		'' => $sys_language->get('common', 'select'),
		(60 * 60) => '1 '.$module_language->get('cookie_durations', 'hour'),
		(60 * 60 * 2) => '2 '.$module_language->get('cookie_durations', 'hours'),
		(60 * 60 * 4) => '4 '.$module_language->get('cookie_durations', 'hours'),
		(60 * 60 * 24) => '1 '.$module_language->get('cookie_durations', 'day')
	);
	
	//Opções de tempo de duração de cookie
	$cookie_expire_options = array(
		'' => $sys_language->get('common', 'select'),
		(60 * 60) => '1 '.$module_language->get('cookie_durations', 'hour'),
		(60 * 60 * 24) => '1 '.$module_language->get('cookie_durations', 'day'),
		(60 * 60 * 24 * 30) => '1 '.$module_language->get('cookie_durations', 'month'),
		(60 * 60 * 24 * 30 * 3) => '3 '.$module_language->get('cookie_durations', 'months'),
		(60 * 60 * 24 * 30 * 12) => '1 '.$module_language->get('cookie_durations', 'year')
	);
	
	//Opções de tema
	$theme_options = array(
		'yellow' => $module_language->get('themes', 'yellow'),
		'blue' => $module_language->get('themes', 'blue'),
		'pink' => $module_language->get('themes', 'pink'),
		'purple' => $module_language->get('themes', 'purple'),
		'green' => $module_language->get('themes', 'green'),
		'red' => $module_language->get('themes', 'red'),
		'orange' => $module_language->get('themes', 'orange'),
		'black' => $module_language->get('themes', 'black')
	);
	
	asort($theme_options);
	$theme_options = array_merge(array('' => $sys_language->get('common', 'select')), $theme_options);
	
	//Opções de idioma
	$language_options = array_merge(array('' => $sys_language->get('common', 'select')), \System\Language::get_available_languages());
	
	//Cria formulário
	$form = new \Form\Form('form');
	$form->set_mode('edit', false);
	$form->set_database_options('conf_general', $fields, 1);
	
	//Cria os campos do formulário
	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\Select('theme', $module_language->get('form', 'theme'), '', array(), $theme_options));
	$form->add_field(new \Form\Select('language', $module_language->get('form', 'language'), '', array(), $language_options));
	$form->add_field(new \Form\Hidden('previous_language', '', $sys_config->get('language')));
	$form->add_field(new \Form\TextInput('key', $module_language->get('form', 'security_key')), '('.$module_language->get('form', 'key_obs').')');
	$form->add_html('</div>');
	
	$form->add_html('<div class="inline-labels grid-3">');
	$form->add_field(new \Form\Select('session_expire', $module_language->get('form', 'session_expire'), '', array(), $session_expire_options), $module_language->get('form', 'session_obs'));
	$form->add_field(new \Form\Select('cookie_expire', $module_language->get('form', 'cookie_expire'), '', array(), $cookie_expire_options), $module_language->get('form', 'cookie_obs'));
	$form->add_field(new \Form\Number('max_image_size', $module_language->get('form', 'max_image_size'), '', array(), 'Mb', false, false, 1));
	$form->add_html('</div>');
	
	$form->add_field(new \Form\Checkbox('display_errors', $module_language->get('form', 'display_errors'), 1), $module_language->get('form', 'display_errors_obs'));
	
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
	if($form->process(false)){
		if($form->get('language') != $form->get('previous_language'))
			\System\Language::reset();
		
		URL\URL::reload();
	}
?>