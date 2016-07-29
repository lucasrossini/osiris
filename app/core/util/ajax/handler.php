<?php
	//Inclui a página selecionada
	$page = HTTP\Request::get('page');
	$result = array('success' => false);
	
	switch($page){
		case 'autocomplete':
		case 'form':
		case 'login':
		case 'more':
		case 'options':
		case 'post':
		case 'thumb':
			header('Content-type: application/json');
			include CORE_PATH.'/util/ajax/'.$page.'.php';
			
			break;
		
		case 'image-upload':
			include CORE_PATH.'/lib/form/image-upload/functions.php';
			break;
		
		case 'uploadifive':
			header('Content-type: application/json');
			include CORE_PATH.'/lib/form/uploadifive/functions.php';
			
			break;
	}
	
	//Retorna o resultado da requisição
	if(array_key_exists('success', $result) && !$result['success'] && empty($result['error']))
		$result['error'] = $sys_language->get('ajax_post_data', 'error_message');
	
	echo json_encode($result);
	exit;
?>