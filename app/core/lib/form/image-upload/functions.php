<?php
	//Extensões permitidas
	$extension_whitelist = array('jpg', 'jpeg');
	
	//Captura a ação desejada
	$action = \HTTP\Request::get('action');
	$result = array('success' => false, 'error' => '');
	
	switch($action){
		case 'upload': //Upload da imagem
			$folder = \HTTP\Request::get('folder');
			\Storage\Folder::fix_path($folder);
			
			$upload = \Storage\File::upload('file', $folder, $extension_whitelist, array(), true, MAX_IMG_UPLOAD_SIZE);
			
			if($upload['success']){
				$dimensions = \Media\Image::get_dimensions($folder.$upload['file']);
				$result = array('success' => true, 'file' => $upload['file'], 'url' => \Security\Crypt::exec($folder.$upload['file']), 'width' => $dimensions['width'], 'height' => $dimensions['height']);
			}
			else{
				$result['error'] = $upload['error'];
			}
				
			break;
		
		case 'crop': //Recorte da imagem
			$filename = \HTTP\Request::post('file');
			$folder = \HTTP\Request::post('folder');
			$prefix = \HTTP\Request::post('prefix');

			\Storage\Folder::fix_path($folder);

			//Valida a imagem
			if(\Form\Validator::is_image(ROOT.$folder.$filename, $extension_whitelist)){
				$new_filename = \Storage\File::validate($folder, $prefix.\Storage\File::name($filename).'.jpg');

				//Recorta a imagem
				if(!\HTTP\Request::post('proportional')){
				   $targ_w = \HTTP\Request::post('w');
				   $targ_h = \HTTP\Request::post('h');
				}
				else{
					$targ_w = \HTTP\Request::post('width');
					$targ_h = \HTTP\Request::post('height');
				}

				$image = new \Media\Image($folder.$filename);
				$image->crop(\HTTP\Request::post('x'), \HTTP\Request::post('y'), \HTTP\Request::post('w'), \HTTP\Request::post('h'), $targ_w, $targ_h);
				$image->save($folder.$new_filename);

				//Apaga a imagem original
				\Storage\File::delete($folder, $filename);

				$thumb_dimensions = ($image->get_width() > 600) ? $image->get_resize_dimensions(600, 0) : array('width' => $image->get_width(), 'height' => $image->get_height());
				$result = array('success' => true, 'file' => $new_filename, 'thumb' => array('file' => \Media\Image::thumb($folder.$new_filename, $thumb_dimensions['width'], $thumb_dimensions['height']), 'width' => $thumb_dimensions['width'], 'height' => $thumb_dimensions['height']));
			}
			
			break;
		
		case 'remove': //Apaga o arquivo
			if(Storage\File::delete(HTTP\Request::post('folder'), HTTP\Request::post('file')))
				$result = array('success' => true);
			
			break;
	}
?>