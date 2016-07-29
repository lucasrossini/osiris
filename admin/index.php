<?php
	//Inicia o buffer de saída
	ob_start('ob_gzhandler');
	
	//Arquivo de inicialização do sistema
	require '../app/conf/bootstrap.php';
	
	//Instancia o administrador
	$sys_user = new \User\Admin();
	
	//Componentes CSS e JS
	$css_list = array(
		array('file' => 'app/assets/js/jquery/plugins/tipsy/jquery.tipsy.css'),
		array('file' => 'app/assets/css/reset.css'),
		array('file' => 'app/assets/css/common.css'),
		array('file' => 'app/assets/css/ajax.css'),
		array('file' => 'app/assets/css/message.css'),
		array('file' => 'admin/assets/css/styles.css'),
		array('file' => 'admin/assets/css/form.css'),
		array('file' => 'admin/assets/css/table.css'),
		array('file' => 'admin/assets/css/pagination.css'),
		array('file' => 'admin/assets/themes/'.ADMIN_THEME.'/css/styles.css')
	);
	
	$js_list = array(
		array('file' => 'app/assets/js/jquery/plugins/jquery.placeholder.min.js'),
		array('file' => 'app/assets/js/jquery/plugins/tipsy/jquery.tipsy.min.js'),
		array('file' => 'app/assets/js/ajax.js'),
		array('file' => 'app/assets/js/common.js'),
		array('file' => 'admin/assets/js/ready.js')
	);
	
	//E-Commerce
	if(ECOMMERCE)
		$css_list[] = array('file' => 'admin/assets/css/ecommerce.css');
	
	$sys_assets = new \System\Assets($css_list, $js_list);
	
	//Controle do sistema
	$sys_control = new \System\Control(DIR_LEVEL + 1, \HTTP\Router::get_admin_routes());
	$sys_control->process_content();
?>

<!DOCTYPE html>
<html lang="<?php echo \System\Language::get_current_lang(); ?>">
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
			$sys_assets->display('admin', 'css');
			
			//Tags de ícones
			echo $sys_control->get_icon_tags();
		?>
		
		<link href="http://fonts.googleapis.com/css?family=Roboto+Slab:400" rel="stylesheet" />
		<link href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700" rel="stylesheet" />

		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
		<script>!window.jQuery && document.write('<script src="app/assets/js/jquery/jquery-1.8.2.min.js"><\/script>')</script>
		
		<!--[if lt IE 9]>
			<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
			<script>!window.html5 && document.write('<script src="app/assets/js/html5/html5shiv.js"><\/script>')</script>
		<![endif]-->
	</head>

	<body id="page" class="admin">
		<?php
			//Aviso de navegador desatualizado
			\HTTP\Browser::show_update_message();
			
			//Barra do topo
			include 'inc/bar.php';
			
			//Cabeçalho
			include 'inc/header.php';
		?>

		<div id="content">
			<?php
				//Menu lateral
				if($sys_control->get_page(0) != 'login')
					include 'inc/menu.php';

				//Conteúdo
				$sys_control->display_content(false, false, true, 'main');
			?>

			<div class="clear"></div>
		</div>

		<?php
			//Script com o idioma do sistema
			echo $sys_language->get_script();
			
			//Rodapé
			include 'inc/footer.php';
			
			//Arquivos CSS (que não foram exibidos)
			$sys_assets->display('admin', 'css');

			//Arquivos JavaScript
			$sys_assets->display('admin', 'js');
		?>
	</body>
</html>

<?php
	//Exibe o buffer de saída
	ob_end_flush();
?>