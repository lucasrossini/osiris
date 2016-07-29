<?php
	//Inicia o buffer de saída
	ob_start('ob_gzhandler');
	
	//Arquivo de inicialização do sistema
	require '../app/conf/bootstrap.php';
	
	//Instancia o usuário
	$sys_user = ECOMMERCE ? new \User\User(\DAO\Ecommerce\Client::TABLE_NAME, '') : new \User\User();
	
	//Instancia objeto do SDK do Facebook
	$sys_facebook_sdk = \Social\Facebook\Facebook::connect();
	
	//Componentes CSS e JS
	$css_list = array(
		array('file' => 'app/assets/css/reset.css'),
		array('file' => 'app/assets/css/common.css'),
		array('file' => 'app/assets/css/ajax.css'),
		array('file' => 'app/assets/css/message.css'),
		array('file' => 'site/assets/css/styles.css'),
		array('file' => 'site/assets/css/form.css'),
		array('file' => 'site/assets/css/pagination.css')
	);
	
	$js_list = array(
		array('file' => 'app/assets/js/jquery/plugins/jquery.placeholder.min.js'),
		array('file' => 'app/assets/js/ajax.js'),
		array('file' => 'app/assets/js/common.js'),
		array('file' => 'site/assets/js/ready.js')
	);
	
	//Controle do sistema
	$sys_control = new \System\Control(DIR_LEVEL, \HTTP\Router::get_site_routes());
	
	//E-Commerce
	if(ECOMMERCE){
		//Redireciona para a página de pedido concluído caso já tenha realizado
		if(HTTP\Session::exists('order_placed') && ($sys_control->get_url() != '/checkout/pedido'))
			URL\URL::redirect('/checkout/pedido');
		
		//Recursos específicos
		$css_list[] = array('file' => 'site/ecommerce/assets/css/styles.css');
		$js_list[] = array('file' => 'app/assets/js/jquery/plugins/jquery.lazyload.min.js');
		$js_list[] = array('file' => 'site/ecommerce/assets/js/ready.js');
	}
	
	$sys_assets = new \System\Assets($css_list, $js_list);
	$sys_control->process_content();
?>

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

			//Descrição do site
			echo $sys_control->get_description_tag();

			//Palavras-chave do site
			echo $sys_control->get_keywords_tag();

			//Meta tags de RSS
			echo $sys_control->get_rss_tag();

			//Meta tags de links canônicos
			echo $sys_control->get_canonical_tag();
			
			//Tags do Facebook
			echo \Social\Facebook\Facebook::get_meta_tags();

			//Tags do Google Plus
			echo \Google\GooglePlus::get_meta_tags();

			//Script do Google Analytics
			echo \Google\GoogleAnalytics::get_script();

			//Arquivos CSS
			$sys_assets->display('site', 'css');
			
			//Tags de ícones
			echo $sys_control->get_icon_tags();
		?>

		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
		<script>!window.jQuery && document.write('<script src="app/assets/js/jquery/jquery-1.8.2.min.js"><\/script>')</script>
		
		<!--[if lt IE 9]>
			<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
			<script>!window.html5 && document.write('<script src="app/assets/js/html5/html5shiv.js"><\/script>')</script>
		<![endif]-->
	</head>

	<body id="page" class="site">
		<?php
			//Script do Facebook
			echo \Social\Facebook\Facebook::get_script();
			
			//Aviso de navegador desatualizado
			\HTTP\Browser::show_update_message();

			//Barra do administrador
			include '../admin/inc/admin-bar.php';
			
			//Cabeçalho
			include 'inc/header.php';

			//Breadcrumb
			echo $sys_control->get_breadcrumb();
		?>
		
		<div id="middle">
			<div class="wrapper">
				<?php
					//Conteúdo
					$sys_control->display_content();
				?>
			</div>
		</div>
		
		<?php
			//Rodapé
			include 'inc/footer.php';
			
			//Script com o idioma do sistema
			echo $sys_language->get_script();
			
			//Arquivos CSS (que não foram exibidos)
			$sys_assets->display('site', 'css');

			//Arquivos JavaScript
			$sys_assets->display('site', 'js');

			//Script do Google Plus
			echo \Google\GooglePlus::get_script();
		?>
	</body>
</html>

<?php
	//Exibe o buffer de saída
	ob_end_flush();
?>