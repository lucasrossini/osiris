<?php
	namespace URL;
	
	/**
	 * Classe para encurtamento de URLs utilizando o serviço bit.ly.
	 * 
	 * @package Osiris
	 * @author Igor Escobar
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 09/04/2014
	*/
	
	class Bitly{
		public $version = '2.0.1';
		public $login = 'grupoemidia';
		public $api_key = 'R_c91b7101261ee451ed7354c6663b0361';
		public $format = 'json';
		public $callback;
		public $url;
		protected $fail = false;
		protected $action = null;
		
		/**
		 * Instancia um objeto do encurtador de URL do bit.ly.
		 * 
		 * @param string $login Login da conta no serviço.
		 * @param string $api_key Chave API gerada pela conta do serviço.
		 */
		public function __construct($login = null, $api_key = null){
			$this->format = strtolower($this->format);
			$this->login = !is_null($login) ? $login : $this->login;
			$this->api_key = !is_null($login) ? $api_key : $this->api_key;
		}
		
		/**
		 * Captura dados através da API.
		 */
		public function get(){
			 if($this->format == 'json'){
				if(!is_object($this->return))
					$this->return = json_decode($this->return);
				
				$this->fail = ($this->return->statusCode == 'ERROR') ? true : false;
			}
		}
		
		/**
		 * Executa uma ação através da API.
		 * 
		 * @param string $action Nome da ação.
		 */
		private function action($action){
			$this->action = $action;
			
			$params = http_build_query(array(
				'version' => $this->version,
				'login' => $this->login,
				'apiKey' => $this->api_key,
				'longUrl' => $this->url,
				'shortUrl' => $this->url,
				'format' => $this->format,
				'callback' => $this->callback
			));
			
			$this->return = $this->get_file_contents('http://api.bit.ly/'.$this->action.'?'.$params);
			$this->get();
		}
		
		/**
		 * Encurta uma URL.
		 * 
		 * @param string $url URL a ser encurtada.
		 * @return string URL encurtada.
		 */
		public function shorten($url = null){
			$this->url = !is_null($url) ? $url : $this->url;
			$this->action('shorten');
			
			return $this->get_data()->shortUrl;
		}
		
		/**
		 * Expande (desencurta) uma URL encurtada.
		 * 
		 * @param string $url URL encurtada a ser expandida.
		 * @return string URL expandida.
		 */
		public function expand($url = null){
			$this->url = !is_null($url) ? $url : $this->url;
			$this->action('expand');
			
			return $this->get_data()->longUrl;
		}
	
		/**
		 * Carrega as informações (e estatísticas) de uma URL encurtada.
		 * 
		 * @param string $url URL encurtada a ser analisada.
		 * @return mixed Dados de resposta da API.
		 */
		public function info($url = null){
			$this->url = !is_null($url) ? $url : $this->url;
			$this->action('info');
			
			return $this->get_data();
		}
	
		/**
		 * Carrega o status de uma URL encurtada.
		 * 
		 * @param string $url URL encurtada a ser analisada.
		 * @return mixed Dados de resposta da API.
		 */
		public function stats($url = null){
			$this->url = !is_null($url) ? $url : $this->url;
			$this->action('stats');
			
			return $this->get_data();
		}
	
		/**
		 * Lê a resposta da API.
		 * 
		 * @return boolean|string|array Dados de resposta da API processados.
		 */
		public function get_data(){
			switch($this->format){
				case 'json':
					if(!$this->fail){
						if($this->return->results){
							$node = reset(array_keys(get_object_vars($this->return->results)));
							return ($this->action != 'stats') ? $this->return->results->$node : $this->return->results;
						}
						else{
							return false;
						}
					}
					else{
						echo '<pre>'.print_r($this->return, false).'</pre>';
					}
					
					break;
				
				case 'xml':
					return $this->return;
			}
			
			return false;
		}
		
		/**
		 * Faz uma requisição para a API.
		 * 
		 * @param string $url Endereço da API.
		 * @return mixed|boolean|string Dados de resposta da API.
		 */
		private function get_file_contents($url){
			if(function_exists('curl_init')){
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_URL, $url);
				$contents = curl_exec($curl);
				curl_close($curl);
	
				return $contents ? $contents : false;
			}
			else{
				return file_get_contents($url);
			}
		}
	}
?>