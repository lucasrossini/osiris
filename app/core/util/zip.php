<?php
	//Valida os parâmetros
	if(!\HTTP\Request::is_set('get', array('f')))
		die($sys_language->get('common', 'invalid_params'));
	
	//Captura os parâmetros
	$files = unserialize(\Security\Crypt::undo(\HTTP\Request::get('f')));
	$delete_after = ((int)\Security\Crypt::undo(\HTTP\Request::get('d')) === 1);
	
	//Compacta os arquivos
	$target_folder = '/temp/';
	$zip_file = sha1(date('dmYHis')).'.zip';
	\Storage\Compressor::zip($files, $target_folder.$zip_file);
	
	//Faz o download do arquivo
	\Storage\Download::get($target_folder, $zip_file, $delete_after);
?>