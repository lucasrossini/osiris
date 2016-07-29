<!DOCTYPE html>
<html lang="<?php echo \System\Language::get_current_lang(); ?>" xmlns:fb="http://ogp.me/ns/fb#" itemscope itemtype="http://schema.org/Article">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta name="generator" content="Osiris" />
		<meta name="author" content="Grupo Emedia" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />

		<?php
			//Título do site
			echo $sys_control->get_title_tag();
			
			//Base do site
			echo $sys_control->get_base_tag();

			//Arquivos CSS
			$sys_assets->clear();
			$sys_assets->load('css', array('/app/assets/css/reset.css', '/app/assets/css/common.css', '/app/assets/css/ajax.css', '/app/assets/css/modal.css', '/admin/assets/themes/'.ADMIN_THEME.'/css/styles.css', '/admin/assets/css/form.css'));
			$sys_assets->load('js', '/app/assets/js/ajax.js');
			$sys_assets->display('site', 'css');
		?>
		
		<link href="http://fonts.googleapis.com/css?family=Roboto+Slab:400" rel="stylesheet" />
		<link href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700" rel="stylesheet" />

		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
		<script>!window.jQuery && document.write('<script src="app/assets/js/jquery/jquery-1.8.2.min.js"><\/script>')</script>
		
		<!--[if lt IE 9]>
			<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
			<script>!window.html5 && document.write('<script src="app/assets/js/html5shiv.js"><\/script>')</script>
		<![endif]-->
	</head>
	
	<?php
		//Carrega a página selecionada
		$page = \HTTP\Request::get('page');
		
		echo '
			<body class="modal '.$page.'">
				<div id="ajax-loader-container">
					<span id="ajax-loader" style="display: none">'.$sys_language->get('common', 'loading').'...</span>
					<span id="ajax-result" style="display: none"></span>
				</div>
		';

		switch($page){
			case 'option-add':
			case 'password-change':
				include ROOT.'/app/core/util/modal/'.$page.'.php';
				break;

			case 'image-upload':
				include ROOT.'/app/core/lib/form/image-upload/upload.php';
				break;
		}

		//Script com o idioma do sistema
		echo $sys_language->get_script();

		//Arquivos CSS (que não foram exibidos)
		$sys_assets->display('site', 'css');

		//Arquivos JavaScript
		$sys_assets->display('site', 'js');
		
		echo '</body>';
	?>
</html>