<?php
	namespace URL;
	
	/**
	 * Classe para manipulação de URLs.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 27/02/2014
	*/
	
	abstract class URL{
		const POST_SESSION = 'redirected_post_vars';
		
		/**
		 * Remove parâmetros GET de uma URL.
		 * 
		 * @param string $url URL a ser tratada.
		 * @param array $params Vetor com os nomes dos parâmetros a serem removidos.
		 * @return string URL com os parâmetros removidos.
		 */
		public static function remove_params($url, $params = array()){
			if(!strpos($url, '?') || !sizeof($params))
				return $url;
			
			$url_pieces = explode('?', $url);
			$url_params = explode('&', $url_pieces[1]);
			$new_params = '';
			
			foreach($url_params as $param){
				$param_name = reset(explode('=', $param));
				
				if(!in_array($param_name, $params))
					$new_params .= $param.'&';
			}
			
			$new_params = rtrim($new_params, '&');
			return $new_params ? $url_pieces[0].'?'.$new_params : $url_pieces[0];
		}
		
		/**
		 * Adiciona parâmetros GET em uma URL.
		 * 
		 * @param string $url URL a ser tratada.
		 * @param string $params Vetor com os parâmetros a serem adicionados, onde a chave é o nome do parâmetro e o valor é o valor do parâmetro.
		 * @return string URL com os parâmetros adicionados.
		 */
		public static function add_params($url, $params = array()){
			if(sizeof($params)){
				$url = self::remove_params($url, array_keys($params));
				
				foreach($params as $param_name => $param_value){
					if(!empty($param_value)){
						$param_value = urlencode($param_value);
						$url .= (strpos($url, '?') === false) ? '?'.$param_name.'='.$param_value : '&'.$param_name.'='.$param_value;
					}
				}
			}
			
			return $url;
		}
		
		/**
		 * Captura o valor de um parâmetro GET de uma URL.
		 * 
		 * @param string $url URL a ser analisada.
		 * @param string $get Nome do parâmetro a ter seu valor capturado.
		 * @return string|boolean Valor do parâmetro em caso do parâmetro existir na URL ou FALSE em caso contrário.
		 */
		public static function get_param($url, $get){
			if(!strpos($url, '?'))
				return false;
			
			$url_pieces = explode('?', $url);
			$params = explode('&', $url_pieces[1]);
			
			foreach($params as $param){
				$param_pieces = explode('=', $param);
				$param_name = $param_pieces[0];
				$param_value = $param_pieces[1];
				
				if($get == $param_name)
					return urldecode($param_value);
			}
			
			return false;
		}
		
		/**
		 * Captura o subdomínio de uma URL.
		 * 
		 * @param string $url URL a ter o subdomínio capturado.
		 * @return string Subdomínio da URL.
		 */
		public static function get_subdomain($url){
			$real_domain = self::get_domain($url);
			$domain = '';
			
			$url_pieces = explode('/', $url);
			$http_host = $url_pieces[2];
			
			$host_pieces = explode('.', $http_host);
			$host_pieces_size = sizeof($host_pieces);
			$i = 0;
			
			while($domain != $real_domain){
				$domain = '';
				
				for($d = $i; $d < $host_pieces_size; $d++)
					$domain .= $host_pieces[$d].'.';
				
				$domain = rtrim($domain, '.');
				$i++;
				
				if($i >= 20)
					break;
			}
			
			$subdomain = '';
			
			for($s = 0; $s < ($i - 1); $s++){
				if($host_pieces[$s] != 'www')
					$subdomain .= $host_pieces[$s].'.';
			}
			
			return rtrim($subdomain, '.');
		}
		
		/**
		 * Captura o domínio de uma URL.
		 * 
		 * @param string $url URL a ter o domínio capturado.
		 * @return string Domínio da URL.
		 */
		public static function get_domain($url){
			$bits = explode('/', $url);
			$url = ($bits[0] == 'http:' || $bits[0] == 'https:') ? $bits[2] : $bits[0];
			
			unset($bits);
			$bits = explode('.', $url);
			$idz = 0;
			
			while(isset($bits[$idz]))
				$idz += 1;
			
			$idz -= 3;
			$idy = 0;
			
			while($idy < $idz){
				unset($bits[$idy]);
				$idy += 1;
			}
			
			$part = array();
			
			foreach($bits as $bit)
				$part[] = $bit;
			
			unset($bit);
			unset($bits);
			unset($url);
			
			if(strlen($part[1]) > 3)
				unset($part[0]);
			
			foreach($part as $bit)
				$domain .= $bit.'.';
			
			unset($bit);
			return preg_replace('/(.*)\./', '$1', $domain);
		}
		
		/**
		 * Redireciona a página.
		 * 
		 * @param string $url Endereço da página de destino do redirecionamento.
		 * @param int $header Código do status de cabeçalho a ser enviado, que pode ser 301 (movido permanentemente).
		 * @param boolean $base_ref Define se o endereço deve utilizar a base do site caso ele não esteja em outro domínio.
		 */
		public static function redirect($url, $header = null, $base_ref = true){
			switch($header){
				case 301:
					header('HTTP/1.1 301 Moved Permanently');
					break;
			}
			
			if($base_ref && DIR_LEVEL && !\Form\Validator::is_url($url))
				$url = BASE.$url;
			
			header('Location: '.$url);
			die();
		}
		
		/**
		 * Recarrega a página.
		 */
		public static function reload(){
			self::redirect(URL, null, false);
		}
		
		/**
		 * Redireciona a página com parâmetros POST.
		 *
		 * @param string $url Endereço da página de destino do redirecionamento.
		 * @param array $post_data Parâmetros POST novos a serem passados (se nenhum dado for passado, utiliza os dados POST atuais).
		 */
		public static function redirect_post($url, $post_data = array()){
			if(!sizeof($post_data))
				$post_data = $_POST;
			
			\HTTP\Session::create(self::POST_SESSION, $post_data);
			self::redirect($url);
		}
		
		/**
		 * Verifica se a página foi alvo de um redirecionamento com parâmetros POST.
		 */
		public static function check_post_redirect(){
			if(\HTTP\Session::exists(self::POST_SESSION)){
				$_POST = \HTTP\Session::get(self::POST_SESSION);
				\HTTP\Session::delete(self::POST_SESSION);
			}
		}
		
		/**
		 * Captura o título de uma URL.
		 *
		 * @param string $url Endereço da página a ter seu título capturado.
		 * @return string|boolean Título da página em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function get_title($url){
			if(\Form\Validator::is_url($url)){
				$page = '';
				$fp = fopen($url, 'r');
				
				while(!feof($fp))
					$page .= fgets($fp, 4096);
				
				fclose($fp);
				
				preg_match_all("/<title>(.*)<\/title>/", $page, $matches);
				return $matches[1][0];
			}
				
			return false;
		}
		
		/**
		 * Carrega e retorna o conteúdo de uma página.
		 * 
		 * @param string $url Endereço da página a ter seu conteúdo carregado.
		 * @return string Conteúdo da página.
		 */
		public static function get_content($url){
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			$data = curl_exec($curl);
			curl_close($curl);
			
			return $data;
		}
	}
?>