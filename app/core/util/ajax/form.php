<?php
	//Captura o objeto de formulário
	$form = unserialize(urldecode(\HTTP\Request::post('object', false)));
	$fields = \HTTP\Request::post('fields', false);
	$after_queries = unserialize(urldecode(\HTTP\Request::post('after_queries', false)));
	$messages = unserialize(urldecode(\HTTP\Request::post('messages', false)));
	
	//Define os valores dos campos
	$form->set_values($fields);
	
	//Define os valores GET/POST
	foreach($fields as $field_name => $field_value)
		\HTTP\Request::set($form->get_method(), $field_name, $field_value);
	
	//Valida o formulário
	$validation_errors = $form->validate(false);
	
	//Trata formulário após o envio
	$process_result = $form->process(false, $after_queries, true, $messages);
	
	//Resultado
	$result = array(
		'valid' => $process_result,
		'error_message' => !$form->is_success() ? array('html' => $validation_errors, 'plain' => $validation_errors) : array('html' => \UI\Message::show_message('error', true, false, false), 'plain' => \UI\Message::get_message('error')),
		'success_message' => array('html' => \UI\Message::show_message('success', true, false, false), 'plain' => \UI\Message::get_message('success')),
		'process_id' => $form->get_process_id()
	);
?>