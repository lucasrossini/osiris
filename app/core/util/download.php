<?php
	//Valida os parâmetros
	if(!\HTTP\Request::is_set('get', array('p', 'f')))
		die($sys_language->get('common', 'invalid_params'));
	
	//Captura os parâmetros
	$path = \Security\Crypt::undo(\HTTP\Request::get('p'));
	$file = \Security\Crypt::undo(\HTTP\Request::get('f'));
	$delete_after = ((int)\Security\Crypt::undo(\HTTP\Request::get('d')) === 1);
	
	//Faz o download do arquivo
	\Storage\Download::get($path, $file, $delete_after);
?>