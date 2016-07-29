<?php
	//Extensões bloqueadas
	$extension_blacklist = array('php', 'php3', 'php4', 'phtml', 'phtm', 'inc', 'exe', 'bat', 'html', 'htaccess');
	
	//Captura a ação desejada
	$action = \HTTP\Request::get('action');
	$result = array('success' => false, 'error' => '');
	
	//Realiza a ação
	if(\HTTP\Request::post('token') == md5(KEY.\HTTP\Request::post('timestamp'))){
		switch($action){
			case 'upload': //Upload do arquivo
				$extension_whitelist = unserialize(\HTTP\Request::post('whitelist', false));
				$thumb_dimensions = HTTP\Request::is_set('post', 'thumb_dimensions') ? unserialize(\HTTP\Request::post('thumb_dimensions', false)) : array();
				$folder = \HTTP\Request::post('folder');
				\Storage\Folder::fix_path($folder);

				$upload = \Storage\File::upload('file', $folder, $extension_whitelist, $extension_blacklist);

				if($upload['success']){
					$result = array('success' => true, 'file' => $upload['file'], 'description' => $upload['file'].' ('.\Storage\File::size($folder, $upload['file'], 'Kb').')');
					
					if(sizeof($thumb_dimensions))
						$result['thumb'] = \Media\Image::thumb($folder.$upload['file'], $thumb_dimensions['width'], $thumb_dimensions['height']);
				}
				else{
					$result['error'] = $upload['error'];
				}

				break;

			case 'remove': //Apaga o arquivo
				if(Storage\File::delete(HTTP\Request::post('folder'), HTTP\Request::post('file')))
					$result = array('success' => true);

				break;
		}
	}
?>