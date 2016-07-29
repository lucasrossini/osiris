<?php
	//Instancia um objeto de configuração
	$sys_config = new \System\Config();
	
	//Chave
	define('KEY', $sys_config->get('key'));
	
	//Tempo de expiração da sessão
	define('SESSION_EXPIRE', $sys_config->get('session_expire'));
	
	//Upload de imagem
	define('MAX_IMG_UPLOAD_SIZE', $sys_config->get('max_image_size'));
	
	//Tempo de expiração de cookies
	define('COOKIE_EXPIRE', time() + $sys_config->get('cookie_expire'));
	
	//Tema da área administrativa
	define('ADMIN_THEME', $sys_config->get('theme'));
	
	//Logomarca na área administrativa
	$logo = $sys_config->get('logo');
	
	if($logo && \Storage\File::exists('/uploads/images/site/'.$logo))
		define('LOGO', 'uploads/images/site/'.$logo);
	
	//Título do site
	define('TITLE', $sys_config->get('title'));
	
	//Subtítulo do site
	if($sys_config->get('subtitle'))
		define('SUBTITLE', $sys_config->get('subtitle'));
	
	//Descrição do site
	if($sys_config->get('description'))
		define('DESCRIPTION', $sys_config->get('description'));
	
	//Palavras-chave do site
	if($sys_config->get('keywords'))
		define('KEYWORDS', $sys_config->get('keywords'));
	
	//ID do Google Analytics
	$conf_ga = new \System\Config('ga');
	
	if($conf_ga->get('ua_id'))
		define('ANALYTICS_ID', $conf_ga->get('ua_id'));
	
	//Domínio
	$conf_server = new \System\Config('server');
	
	switch($_SERVER['SERVER_ADDR']){
		case LOCALHOST_IP: //Máquina local
			$url_field = 'url_local';
			break;
		
		case LOCAL_SERVER_IP: //Servidor local
			$url_field = 'url_server';
			break;
		
		default: //Internet
			$protocol = SSL ? 'https' : 'http';
			
			switch($conf_server->get('use_www')){
				case 1: //Usar sempre 'www'
					if(substr($_SERVER['HTTP_HOST'], 0, 3) != 'www')
						\URL\URL::redirect($protocol.'://www.'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 301);

					break;

				case 2: //Nunca usar 'www'
					if(substr($_SERVER['HTTP_HOST'], 0, 3) == 'www')
						\URL\URL::redirect($protocol.'://'.substr($_SERVER['HTTP_HOST'], 4, strlen($_SERVER['HTTP_HOST']) - 1).$_SERVER['REQUEST_URI'], 301);

					break;
			}

			$url_field = 'url_web';
	}
	
	define('BASE_ROOT', rtrim($conf_server->get($url_field), '/'));
	define('BASE', BASE_ROOT.rtrim(DIR, '/'));
	
	//Subdomínio
	$subdomain = \URL\URL::get_subdomain(BASE);
	
	if(!empty($subdomain)){
		ini_set('session.cookie_domain', '.'.$subdomain.'.'.\URL\URL::get_domain(BASE));
		ini_set('suhosin.session.cryptdocroot', 'Off');
		ini_set('suhosin.cookie.cryptdocroot', 'Off');
	}
	
	//Exibição de mensagens de erro de código no site
	$display_errors = (\HTTP\Server::is_local() || $sys_config->get('display_errors')) ? 1 : 0;
	ini_set('display_errors', $display_errors);
	
	//E-mail
	$conf_email = new \System\Config('email');
	
	define('MAIL_HOST', $conf_email->get('host'));
	define('MAIL_USER', $conf_email->get('user'));
	define('MAIL_PASS', \Security\Crypt::undo($conf_email->get('password')));
	
	//URL atual
	define('URL', BASE_ROOT.'/'.ltrim(urldecode($_SERVER['REQUEST_URI']), '/'));
	
	//URL atual limpa, sem parâmetros GET
	define('URL_WITHOUT_GETS', reset(explode('?', URL)));
	
	//Verifica se a página foi acessada sem HTTPS e ela aceita
	if(SSL && \HTTP\Server::is_web_server()){
		$url_without_protocol = end(explode('://', URL, 2));
		
		if(empty($_SERVER['HTTPS']))
			\URL\URL::redirect('https://'.$url_without_protocol, 301);
	}
	
	//Verifica se a página foi redirecionada com parâmetros POST (e recebe esses parâmetros)
	\URL\URL::check_post_redirect();
	
	//Define o idioma do sistema
	$sys_language = new \System\Language();
	
	//Data atual por extenso
	define('CURRENT_LONG_DATE', \DateTime\Date::get_current_long_date());
?>