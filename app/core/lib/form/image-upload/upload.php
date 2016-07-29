<?php
	//Captura os parâmetros
	$id = \HTTP\Request::get('id');
	$width = (int)\HTTP\Request::get('width');
	$height = (int)\HTTP\Request::get('height');
	$folder = \HTTP\Request::get('folder');
	$proportional = (int)\HTTP\Request::get('proportional');
	$max_width = (int)\HTTP\Request::get('max_width');
	$max_height = (int)\HTTP\Request::get('max_height');
	$prefix = \HTTP\Request::get('prefix');
	
	//Carrega os recursos necessários
	$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.color.pack.js', array('charset' => 'ISO-8859-1'));
	$sys_assets->load('js', 'app/core/lib/form/image-upload/js/jquery.ajaxfileupload.pack.js', array('charset' => 'ISO-8859-1'));
	$sys_assets->load('js', 'app/core/lib/form/image-upload/js/jquery.jcrop.min.js');
	$sys_assets->load('js', 'app/core/lib/form/image-upload/js/upload.js');
	$sys_assets->load('css', 'app/core/lib/form/image-upload/css/jcrop.css');
	$sys_assets->load('css', 'app/core/lib/form/image-upload/css/styles.css');
?>

<div id="image-upload-container" class="modal">
	<header id="header">
		<hgroup>
			<h1><span class="icon"><?php echo $sys_language->get('image_upload', 'image_upload') ?></span></h1>
			<h2><?php printf($sys_language->get('image_upload', 'description'), '<strong>.jpg</strong>', MAX_IMG_UPLOAD_SIZE) ?></h2>
		</hgroup>
	</header>
	
	<div class="content">
		<form id="crop" method="post">
			<div class="buttons">
				<button type="button" class="button" id="crop-button"><?php echo $sys_language->get('image_upload', 'crop_image') ?></button>
				<button type="button" class="button" id="cancel-button"><?php echo $sys_language->get('common', 'cancel') ?></button>
			</div>
		</form>
		
		<form id="upload" method="post" enctype="multipart/form-data">
			<input type="file" name="file" id="file" />
			<button type="button" id="upload-button" class="button"><?php echo $sys_language->get('image_upload', 'send_image') ?></button>
		</form>
	</div>
</div>

<script>
	$(document).ready(function(){
		//Monta o objeto de upload de imagem
		var current_upload_obj = new ImageUpload(<?php echo '"'.$id.'", '.$width.', '.$height.', "'.$folder.'", '.$proportional.', '.$max_width.', '.$max_height.', "'.$prefix.'"' ?>);
		
		//Aplica as funções aos botões
		$('#upload-button').click(function(){
			current_upload_obj.upload($(this));
		});

		$('#crop-button').click(function(){
			current_upload_obj.crop($(this));
		});

		$('#cancel-button').click(function(){
			current_upload_obj.cancel($(this));
		});
	});
</script>