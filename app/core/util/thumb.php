<?php
	//Valida os parâmetros
	if(!\HTTP\Request::is_set('get', array('image', 'width', 'height', 'type')))
		die($sys_language->get('common', 'invalid_params'));
	
	//Captura os parâmetros
	$filename = \Security\Crypt::undo(\HTTP\Request::get('image'));
	$type = strtolower(\HTTP\Request::get('type'));
	$width = (int)\HTTP\Request::get('width');
	$height = (int)\HTTP\Request::get('height');
	$force = (int)\HTTP\Request::get('force');
	$cache = (int)\HTTP\Request::get('cache');
	$quality = \HTTP\Request::is_set('get', 'quality') ? (int)\HTTP\Request::get('quality') : \Media\Image::MAX_QUALITY;
	
	//Valida o tipo de imagem
	if(!in_array($type, array('jpg', 'jpeg', 'gif', 'png')))
		die($sys_language->get('thumb', 'invalid_image_type'));
	
	//Instancia a imagem
	$image = new \Media\Image($filename);
	
	if($image->is_valid()){
		//Redimensiona a imagem
		if(!$width)
			$image->resize_height($height);
		elseif(!$height)
			$image->resize_width($width);
		else
			$image->smart_resize($width, $height, $force);
		
		//Salva o arquivo da imagem no cache
		if($cache){
			$cache_file = \Storage\Cache::DIR.sha1($filename.'-'.$width.'x'.$height).'.'.$type;
			$image->save($cache_file);

			//Define a data de modificação do arquivo de cache como a mesma data de modificação do arquivo original
			if(!$image->is_external())
				@touch(ROOT.$cache_file, filemtime(ROOT.$filename));
		}
		
		//Exibe a imagem
		$image->output($type, $quality);
	}
	
	die($sys_language->get('thumb', 'invalid_image'));
?>