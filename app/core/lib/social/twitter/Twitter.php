<?php
	namespace Social\Twitter;
	
	/**
	 * Classe para integração com o Twitter.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 28/01/2014
	*/
	
	abstract class Twitter{
		/**
		 * Exibe o botão de "Tweetar" para uma página.
		 *
		 * @param string $url Endereço da página a ser tweetada.
		 * @param string $text Texto a ser tweetado.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public static function tweet_button($url = URL_WITHOUT_GETS, $text = '', $echo = false){
			//Carrega as configurações do Twitter
			$conf_twitter = new \System\Config('twitter');
			
			$data_via = $conf_twitter->get('username') ? 'data-via="'.$conf_twitter->get('username').'"' : '';
			
			$button = '
				<a href="https://twitter.com/share" class="twitter-share-button" data-url="'.$url.'" data-text="'.$text.'" '.$data_via.' data-lang="pt">Tweetar</a>
				<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
			';
			
			if($echo)
				echo $button;
			else
				return $button;
		}
		
		/**
		 * Exibe o botão de seguir do Twitter para um perfil.
		 * 
		 * @param string $username Nome do perfil.
		 * @param boolean $large Define se o botão deve ser do tamanho grande.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public static function follow_button($username, $large = false, $echo = false){
			$data_size = $large ? 'data-size="large"' : '';
			
			$button = '
				<a href="https://twitter.com/'.$username.'" class="twitter-follow-button" data-show-count="false" data-lang="pt" '.$data_size.'>Siga @'.$username.'</a>
				<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
			';
			
			if($echo)
				echo $button;
			else
				return $button;
		}
		
		/*-- Twitter API --*/
		
		/**
		 * Conecta o sistema a um aplicativo do Twitter.
		 * 
		 * @param string $oauth_access_token Token de acesso OAuth.
		 * @param string $oauth_access_token_secret Segredo do token de acesso OAuth.
		 * @param string $consumer_key Chave do consumidor.
		 * @param string $consumer_secret Segredo do consumidor.
		 * @return TwitterAPIExchange Objeto da API do Twitter.
		 */
		public static function connect($oauth_access_token = '', $oauth_access_token_secret = '', $consumer_key = '', $consumer_secret = ''){
			//Carrega a chave do aplicativo
			if(empty($oauth_access_token) && empty($oauth_access_token_secret) && empty($consumer_key) && empty($consumer_secret)){
				$conf_twitter = new \System\Config('twitter');
				
				$oauth_access_token = $conf_twitter->get('oauth_access_token');
				$oauth_access_token_secret = $conf_twitter->get('oauth_access_token_secret');
				$consumer_key = $conf_twitter->get('consumer_key');
				$consumer_secret = $conf_twitter->get('consumer_secret');
			}
			
			if(empty($oauth_access_token) && empty($oauth_access_token_secret) && empty($consumer_key) && empty($consumer_secret))
				return false;
			
			//Inclui a API do Twitter
			require_once CORE_PATH.'/lib/social/twitter/api/TwitterAPIExchange.php';
			
			$settings = array(
				'oauth_access_token' => $oauth_access_token,
				'oauth_access_token_secret' => $oauth_access_token_secret,
				'consumer_key' => $consumer_key,
				'consumer_secret' => $consumer_secret
			);
			
			return new \TwitterAPIExchange($settings);
		}
		
		/**
		 * Carrega os últimos tweets de um perfil.
		 * 
		 * @param string $username Nome do perfil.
		 * @param int $count Quantidade de tweets a serem carregados.
		 * @return array Vetor com os tweets, onde o índice 'text' indica o texto do tweet, o índice 'date' indica a data do tweet, o índice 'time' indica a hora do tweet e o índice 'url' indica a URL do tweet.
		 */
		public static function get_latest_tweets($username, $count = 3){
			$twitter = self::connect();
			$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
			
			$params = array(
				'screen_name' => $username,
				'count' => $count,
				'include_rts' => 'true'
			);
			
			$get_fields = \URL\URL::add_params('', $params);
			
			$latest_tweets = array();
			$tweets = json_decode($twitter->setGetfield($get_fields)->buildOauth($url, 'GET')->performRequest());
			
			if(sizeof($tweets)){
				foreach($tweets as $tweet)
					$latest_tweets[] = array('text' => $tweet->text, 'date' => date('d/m/Y', strtotime($tweet->created_at)), 'time' => date('H:i:s', strtotime($tweet->created_at)), 'url' => 'http://twitter.com/'.$username.'/status/'.$tweet->id_str);
			}
			
			return $latest_tweets;
		}
		
		/**
		 * Carrega a foto de um perfil.
		 * 
		 * @param string $username Nome do perfil.
		 * @return string URL da foto.
		 */
		public static function get_profile_image($username){
			$twitter = self::connect();
			
			$url = 'https://api.twitter.com/1.1/users/show.json';
			$params = array('screen_name' => $username);
			
			$get_fields = \URL\URL::add_params('', $params);
			$profile_data = json_decode($twitter->setGetfield($get_fields)->buildOauth($url, 'GET')->performRequest());
			
			return $profile_data->profile_image_url;
		}
		
		/**
		 * Posta no Twitter através do perfil conectado.
		 * 
		 * @param string $text Texto a ser tweetado.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function post($text){
			$twitter = self::connect();
			$url = 'https://api.twitter.com/1.1/statuses/update.json';
			
			$params = array('status' => $text);
			$result = json_decode($twitter->buildOauth($url, 'POST')->setPostfields($params)->performRequest());
			
			return (is_array($result) && !array_key_exists('errors', $result));
		}
	}
?>