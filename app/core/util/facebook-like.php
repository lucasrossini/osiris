<?php
	//Captura os parâmetros
	$class = \HTTP\Request::get('class');
	$id = \HTTP\Request::get('id');
	
	if(class_exists($class) && $id){
		//Cria o objeto do registro
		$object = new $class($id);
		
		//Exibe o HTML da página
		if($object->get('id') && method_exists($object, 'get_facebook_tags')){
			echo '
				<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml" xmlns:og="http://opengraph.org/schema/">
					<head>
						<title></title>
						'.$class::get_facebook_tags($id).'
						<meta http-equiv="refresh" content="0;url='.$object->get('url').'" />
					</head>
					
					<body></body>
				</html>
			';
			
			exit();
		}
	}
	
	die($sys_language->get('common', 'invalid_params'));
?>