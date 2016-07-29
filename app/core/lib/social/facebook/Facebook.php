<?php
	namespace Social\Facebook;
	
	/**
	 * Classe para integração com o Facebook.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 07/03/2014
	*/
	
	abstract class Facebook{
		const LOGGED_PARAM = 'facebook_logged';
		const UNLOGGED_PARAM = 'facebook_unlogged';
		const FACEBOOK_ID_FIELD = 'facebook_id';
		
		/**
		 * Retorna as meta tags do OpenGraph para a página atual.
		 * 
		 * @return string HTML das tags.
		 */
		public static function get_meta_tags(){
			global $sys_control;
			
			//Carrega as configurações do Facebook
			$conf_facebook = new \System\Config('facebook');
			
			$has_tags = false;
			$app_id_tag = $conf_facebook->get('app_id') ? '<meta property="fb:app_id" content="'.$conf_facebook->get('app_id').'" />' : '';
			
			//Tags em comum
			$tags = '
				'.$app_id_tag.'
				<meta property="og:site_name" content="'.TITLE.'" />
			';
			
			//Tags específicas da página atual
			$current_page = $sys_control->get_url();
			$class = $sys_control->get_page_attr($current_page, 'class_name');
			$record_id = $sys_control->get_page_attr($current_page, 'record_id');
			
			if(!empty($class) && !empty($record_id)){
				$reflection_class = new \ReflectionClass($class);
				
				if($reflection_class->isSubclassOf('\Database\DatabaseObject') && $reflection_class->hasProperty('facebook_data')){
					$tags .= $class::get_facebook_tags($record_id);
					$has_tags = true;
				}
			}
			
			//Tags padrão caso a página não possua tags específicas
			if(!$has_tags){
				$tags .= '
					<meta property="og:title" content="'.$sys_control->get_title().'" />
					<meta property="og:type" content="website" />
					<meta property="og:image" content="'.BASE.'/site/media/images/facebook/logo.png" />
					<meta property="og:url" content="'.URL.'" />
				';
				
				$description = '';
				
				if($sys_control->get_page_attr($sys_control->get_url(), 'subtitle'))
					$description = $sys_control->get_page_attr($sys_control->get_url(), 'subtitle');
				elseif(defined('DESCRIPTION'))
					$description = DESCRIPTION;
				
				if($description)
					$tags .= '<meta property="og:description" content="'.$description.'" />';
			}
			
			return $tags;
		}
		
		/**
		 * Exibe a caixa de curtir uma fan page.
		 * 
		 * @param int $page ID da página.
		 * @param int $width Comprimento da caixa.
		 * @param int $height Altura da caixa.
		 * @return string HTML da tag.
		 */
		public static function like_box($page, $width = 300, $height = 400){
			if(!\HTTP\Server::is_local() && !empty($page))
				return '<iframe src="http://www.facebook.com/plugins/likebox.php?href=http%3A%2F%2Fwww.facebook.com%2Fpages%2Fp%2F'.$page.'&amp;width='.$width.'&amp;colorscheme=light&amp;show_faces=true&amp;show_border=false&amp;stream=false&amp;header=false&amp;height='.$height.'" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'.$width.'px; height:'.$height.'px; background:#FFF" allowTransparency="true"></iframe>';
		}
		
		/**
		 * Exibe o botão de "Curtir" para uma URL.
		 * 
		 * @param string $url Endereço da página a ser curtida.
		 * @param boolean $show_faces Define se as fotos de alguns perfis que já curtiram devem ser exibidas.
		 * @param boolean $send Define se o botão deve ser do tipo 'Recomendar'.
		 * @param string $color Cor do botão, que pode ser 'light' ou 'dark'.
		 * @param string $layout Layout do botão, que pode ser 'standard', 'button_count' ou 'box_count'.
		 * @param int $width Comprimento da caixa que envolve o botão.
		 * @return string HTML da tag.
		 */
		public static function like_button($url = URL_WITHOUT_GETS, $show_faces = false, $send = false, $color = 'light', $layout = 'button_count', $width = 80){
			//Carrega as configurações do Facebook
			$conf_facebook = new \System\Config('facebook');
			
			if(!in_array($layout, array('standard', 'button_count', 'box_count')))
				$layout = 'button_count';
			
			return '<fb:like href="'.$url.'" profile_id="'.$conf_facebook->get('page_id').'" send="'.\Formatter\String::bool2string($send).'" action="like" layout="'.$layout.'" width="'.$width.'" show_faces="'.\Formatter\String::bool2string($show_faces).'" colorscheme="'.$color.'"></fb:like>';
		}
		
		/**
		 * Carrega a contagem de curtidas de uma URL.
		 * 
		 * @param string $url URL a ser verificada.
		 * @return int Total de curtidas da URL.
		 */
		public static function likes_count($url){
			$json = json_decode(\URL\URL::get_content('https://graph.facebook.com/?ids='.$url));
			return (int)$json->$url->shares;
		}
		
		/**
		 * Exibe a caixa de comentários para uma página.
		 * 
		 * @param string $url Endereço da página que contém os comentários.
		 * @param int $count Quantidade de comentários a serem exibidos.
		 * @param int $width Comprimento da caixa.
		 * @return string HTML da tag.
		 */
		public static function comments_box($url = URL_WITHOUT_GETS, $count = 10, $width = 600){
			return '<fb:comments href="'.$url.'" num_posts="'.$count.'" width="'.$width.'"></fb:comments>';
		}
		
		/**
		 * Carrega a contagem de comentários de uma página.
		 * 
		 * @param string $url Endereço da página que contém os comentários.
		 * @return string HTML da tag.
		 */
		public static function comments_count($url = URL_WITHOUT_GETS){
			return '<fb:comments-count href="'.$url.'"></fb:comments-count>';
		}
		
		/**
		 * Carrega o script do Facebook.
		 *
		 * @param boolean $javascript_sdk Define se o script deve carregar o SDK javascript.
		 * @return string Script montado.
		 */
		public static function get_script($javascript_sdk = false){
			$script = '
				<div id="fb-root"></div>
				<script>
			';
			
			//Idioma
			$lang = str_replace('-', '_', \System\Language::get_current_lang(true));
			
			if($lang == 'en')
				$lang = 'en_US';
			
			//Javascript SDK
			if($javascript_sdk){
				$conf_facebook = new \System\Config('facebook');
				
				$script .= '
					window.fbAsyncInit = function(){
						FB.init({
							appId: "'.$conf_facebook->get('app_id').'",
							channelUrl: "//'.BASE.'/app/core/lib/social/facebook/sdk/src/channel",
							status: true,
							cookie: true,
							oauth: true,
							xfbml: true
						});	
					};
				';
			}
			
			$script .= '
					(function(d, s, id) {
						var js, fjs = d.getElementsByTagName(s)[0];
						if (d.getElementById(id)) return;
						js = d.createElement(s); js.id = id;
						js.src = "//connect.facebook.net/'.$lang.'/all.js#xfbml=1";
						fjs.parentNode.insertBefore(js, fjs);
					}(document, "script", "facebook-jssdk"));
				</script>
			';
				
			return $script;
		}
		
		/*-- Facebook SDK --*/
		
		/**
		 * Conecta o sistema a um aplicativo do Facebook.
		 * 
		 * @param string $app_id ID do aplicativo.
		 * @param string $app_secret Chave secreta do aplicativo.
		 * @param string $login_redirect URL de redirecionamento após o login no Facebook.
		 * @param string $logout_redirect URL de redirecionamento após o logout no Facebook.
		 * @param string $scope Permissões do usuário para o aplicativo, separadas por vírgula. (https://developers.facebook.com/docs/reference/login/)
		 * @return array Vetor com os índices 'facebook', que contém o objeto principal do Facebook; 'user', que contém o usuário logado do Facebook; 'login_link', que indica a URL de login para o Facebook; e 'logout_link', que indica a URL de logout do Facebook. Ou NULL caso o ID e o segredo do aplicativo não forem definidos.
		 */
		public static function connect($app_id = '', $app_secret = '', $login_redirect = '/login', $logout_redirect = '/logout', $scope = 'email,user_birthday,user_about_me,publish_actions'){
			//Carrega a chave do aplicativo
			if(empty($app_id) && empty($app_secret)){
				$conf_facebook = new \System\Config('facebook');
				
				$app_id = $conf_facebook->get('app_id');
				$app_secret = $conf_facebook->get('app_secret');
			}
			
			if(empty($app_id) && empty($app_secret))
				return null;
			
			require_once CORE_PATH.'/lib/social/facebook/sdk/src/facebook.php';
			
			//Remove os dados de sessão ao efetuar logout
			if(\HTTP\Request::get(self::UNLOGGED_PARAM)){
				self::logout();
				\URL\URL::redirect(\URL\URL::remove_params(URL, array(self::UNLOGGED_PARAM)));
			}
			
			$base_url = $login_redirect;
			$login_redirect = \URL\URL::add_params($login_redirect, array(self::LOGGED_PARAM => 1));
			
			//Instancia um objeto do SDK do Facebook
			$facebook = new \FacebookSDK(array('appId' => $app_id, 'secret' => $app_secret, 'cookie' => true));
			
			$params_login = array('scope' => $scope, 'redirect_uri' => BASE.$login_redirect);
			$params_logout = array('next' => BASE.$logout_redirect);
			
			//Captura o usuário
			$user = $facebook->getUser();
			
			$facebook_login_url = $facebook->getLoginUrl($params_login);
			$facebook_logout_url = $facebook->getLogoutUrl($params_logout);
			
			return array('facebook_sdk' => $facebook, 'user' => $user, 'login_url' => $facebook_login_url, 'logout_url' => $facebook_logout_url, 'base_url' => $base_url);
		}
		
		/**
		 * Verifica se o acesso ao aplicativo do Facebook foi negado.
		 * 
		 * @return boolean TRUE caso tenha sido negado ou FALSE caso tenha sido autorizado.
		 */
		public static function access_denied(){
			return (\HTTP\Request::get(self::LOGGED_PARAM) && (\HTTP\Request::get('error') == 'access_denied'));
		}
		
		/**
		 * Verifica se o perfil do Facebook está logado.
		 * 
		 * @return boolean TRUE caso esteja logado ou FALSE caso não esteja logado.
		 */
		public static function is_logged(){
			global $sys_facebook_sdk;
			return $sys_facebook_sdk['user'] ? true : false;
		}
		
		/**
		 * Checa a permissão de uma determinada ação para o perfil do usuário logado.
		 * 
		 * @param string $permission Nome da permissão. (https://developers.facebook.com/docs/reference/login/)
		 * @return boolean TRUE caso possua a permissão ou FALSE caso não possua a permissão.
		 */
		public static function check_permission($permission){
			global $sys_facebook_sdk;
			$facebook = $sys_facebook_sdk['facebook_sdk'];
			
			if($sys_facebook_sdk['user']){
				$permissions = $facebook->api('/me/permissions');
				return array_key_exists($permission, $permissions['data'][0]);
			}
			
			return false;
		}
		
		/**
		 * Checa se o usuário curte uma página.
		 * 
		 * @param string $page_id ID da página.
		 * @return boolean TRUE caso ele curta ou FALSE caso ele não curta.
		 */
		public static function check_like($page_id){
			global $sys_facebook_sdk;
			$facebook = $sys_facebook_sdk['facebook_sdk'];
			
			if($sys_facebook_sdk['user']){
				//$likes = $facebook->api('/me/likes/'.$page_id);
				$like_data = $facebook->api(array('method' => 'fql.query', 'query' => 'SELECT uid FROM page_fan WHERE uid = me() AND page_id = "'.$page_id.'"'));
				return (sizeof($like_data) > 0);
			}
			
			return false;
		}
		
		/**
		 * Efetua logout do aplicativo do Facebook.
		 * 
		 * @param boolean $facebook Define se deve efetuar logout do perfil do usuário conectado no Facebook.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function logout($facebook = false){
			global $sys_facebook_sdk;
			
			//Carrega as configurações do Facebook
			$conf_facebook = new \System\Config('facebook');
			$app_id = $conf_facebook->get('app_id');
			
			if($facebook && $sys_facebook_sdk){
				$token = $sys_facebook_sdk['facebook_sdk']->getAccessToken();
				$redirect_url = \URL\URL::add_params(BASE.$sys_facebook_sdk['base_url'], array(self::UNLOGGED_PARAM => '1'));
				
				\URL\URL::redirect('https://www.facebook.com/logout.php?next='.$redirect_url.'&access_token='.$token);
			}
			else{
				return \HTTP\Session::delete(array('fb_'.$app_id.'_code', 'fb_'.$app_id.'_access_token', 'fb_'.$app_id.'_user_id', 'fb_'.$app_id.'_state'));
			}
		}
		
		/**
		 * Carrega informações do usuário logado através do Facebook.
		 * 
		 * @param array $data Vetor com as informações desejadas.
		 * @return array Vetor com os valores das informações carregadas ou FALSE em caso de falha.
		 */
		public static function get_user_data($data = array()){
			global $sys_facebook_sdk;
			$facebook = $sys_facebook_sdk['facebook_sdk'];
			
			if($sys_facebook_sdk['user'])
				return is_array($data) ? $facebook->api('/me?fields='.implode(',', $data)) : $facebook->api('/me');
			
			return false;
		}
		
		/**
		 * Carrega a foto de perfil do usuário.
		 * 
		 * @param string $user_id ID do usuário no Facebook.
		 * @param string $type Tipo de foto, que pode ser 'small', 'normal', 'large' e 'square' para tamanhos pré-definidos ou 'custom' para um tamanho customizado.
		 * @param int $width Comprimento da foto.
		 * @param int $height Altura da foto.
		 * @return string|boolean URL da foto em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function get_profile_photo($user_id, $type = 'normal', $width = 150, $height = 150){
			$url = 'http://graph.facebook.com/'.$user_id.'/picture';

			switch($type){
				case 'small':
				case 'normal':
				case 'large':
				case 'square':
					return \URL\URL::add_params($url, array('type' => $type));

				case 'custom':
					return \URL\URL::add_params($url, array('width' => $width, 'height' => $height));
				
				default:
					return false;
			}
		}

		/**
		 * Posta uma mensagem no mural do perfil do Facebook.
		 * 
		 * @param array $params Vetor com os parâmetros para criação do post. (https://developers.facebook.com/docs/reference/api/user/#posts)
		 * @return string|boolean ID do post criado em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function wall_post($params = array()){
			global $sys_facebook_sdk;
			
			if(self::check_permission('publish_actions')){
				$facebook = $sys_facebook_sdk['facebook_sdk'];
				return $facebook->api('/me/feed', 'post', $params);
			}
			
			return false;
		}
		
		/**
		 * Posta uma imagem no álbum do usuário.
		 * 
		 * @param string $image Caminho relativo da imagem no servidor.
		 * @param string $message Mensagem a ser postada junto com a imagem.
		 * @return array|boolean Vetor com dados da foto postada em caso de sucesso (índices 'id' para ID da foto e 'post_id' para ID do post no mural criado) ou FALSE em caso de falha.
		 */
		public static function album_post($image, $message){
			global $sys_facebook_sdk;
			$facebook = $sys_facebook_sdk['facebook_sdk'];
			
			if($sys_facebook_sdk['user']){
				$facebook->setFileUploadSupport(true);

				$photo_params = array(
					'message' => $message,
					'image' => '@'.ROOT.$image
				);

				return $facebook->api('/me/photos', 'post', $photo_params);
			}
			
			return false;
		}
		
		/**
		 * Marca um usuário em uma foto.
		 * 
		 * @param string $photo_id ID da foto.
		 * @param string $user_id ID do usuário a ser marcado.
		 * @param array $coordinates Vetor de coordenadas (em porcentagem) da foto onde o usuário deve ser marcado, com os índices 'x' e 'y'.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function tag_photo($photo_id, $user_id, $coordinates = array('x' => 0, 'y' => 0)){
			global $sys_facebook_sdk;
			return $sys_facebook_sdk['user'] ? $sys_facebook_sdk['facebook_sdk']->api('/'.$photo_id.'/tags/'.$user_id, 'post', $coordinates) : false;
		}
	}
?>