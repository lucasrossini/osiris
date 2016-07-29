<?php
	namespace HTTP\REST;
	
	/**
	 * Classe para realização de requisições REST.
	 * 
	 * @package Osiris
	 * @author Ian Selby
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 21/10/2013
	*/
	
	class Request{
		private $url;
		private $method;
		private $request_body;
		private $request_length;
		private $username;
		private $password;
		private $accept_type;
		private $response_body;
		private $response_info;
		
		/**
		 * Instancia um objeto de requisição REST.
		 * 
		 * @param string $url URL a ser requisitada.
		 * @param string $method Método da requisição (GET, POST, PUT ou DELETE).
		 * @param array $request_body Vetor com os dados da requisição.
		 */
		public function __construct($url = null, $method = 'GET', $request_body = null){
			$this->url = $url;
			$this->method = $method;
			$this->request_body = $request_body;
			$this->request_length = 0;
			$this->username = null;
			$this->password = null;
			$this->accept_type = 'application/json';
			$this->response_body = null;
			$this->response_info = null;

			if($this->request_body !== null)
				$this->build_post_body();
		}
		
		/**
		 * Limpa a requisição.
		 */
		public function flush(){
			$this->request_body = null;
			$this->request_length = 0;
			$this->method = 'GET';
			$this->response_body = null;
			$this->response_info = null;
		}
		
		/**
		 * Realiza a requisição REST.
		 */
		public function execute(){
			$ch = curl_init();
			$this->set_auth($ch);

			try{
				switch(strtoupper($this->method)){
					case 'GET':
						$this->execute_get($ch);
						break;
					
					case 'POST':
						$this->execute_post($ch);
						break;
					
					case 'PUT':
						$this->execute_put($ch);
						break;
					
					case 'DELETE':
						$this->execute_delete($ch);
						break;
					
					default:
						throw new InvalidArgumentException('Current method ('.$this->method.') is an invalid REST method.');
				}
			}
			catch(InvalidArgumentException $e){
				curl_close($ch);
				throw $e;
			}
			catch(Exception $e){
				curl_close($ch);
				throw $e;
			}
		}
		
		/**
		 * Monta o corpo da requisição.
		 * 
		 * @param array $data Vetor de dados da requisição.
		 */
		public function build_post_body($data = null){
			$data = ($data !== null) ? $data : $this->request_body;

			if(!is_array($data))
				throw new InvalidArgumentException('Invalid data input for postBody. Array expected');

			$data = http_build_query($data, '', '&');
			$this->request_body = $data;
		}
		
		/**
		 * Executa uma requisição GET.
		 * 
		 * @param resource $ch Objeto cURL.
		 */
		private function execute_get($ch){
			$this->do_execute($ch);
		}
		
		/**
		 * Executa uma requisição POST.
		 * 
		 * @param resource $ch Objeto cURL.
		 */
		private function execute_post($ch){
			if(!is_string($this->request_body))
				$this->build_post_body();

			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request_body);
			curl_setopt($ch, CURLOPT_POST, 1);

			$this->do_execute($ch);
		}
		
		/**
		 * Executa uma requisição PUT.
		 * 
		 * @param resource $ch Objeto cURL.
		 */
		private function execute_put($ch){
			if(!is_string($this->request_body))
				$this->build_post_body();

			$this->request_length = strlen($this->request_body);

			$fh = fopen('php://memory', 'rw');
			fwrite($fh, $this->request_body);
			rewind($fh);

			curl_setopt($ch, CURLOPT_INFILE, $fh);
			curl_setopt($ch, CURLOPT_INFILESIZE, $this->request_length);
			curl_setopt($ch, CURLOPT_PUT, true);

			$this->do_execute($ch);

			fclose($fh);
		}
		
		/**
		 * Executa uma requisição DELETE.
		 * 
		 * @param resource $ch Objeto cURL.
		 */
		private function execute_delete($ch){
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			$this->do_execute($ch);
		}
		
		/**
		 * Executa a requisição.
		 * 
		 * @param resource $ch Objeto cURL.
		 */
		private function do_execute(&$ch){
			$this->set_curl_opts($ch);
			$this->response_body = curl_exec($ch);
			$this->response_info = curl_getinfo($ch);

			curl_close($ch);
		}
		
		/**
		 * Define as opções cURL.
		 * 
		 * @param resource $ch Objeto cURL.
		 */
		private function set_curl_opts(&$ch){
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_URL, $this->url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: '.$this->accept_type));
		}
		
		/**
		 * Define autenticação na requisição cURL.
		 * 
		 * @param resource $ch Objeto cURL.
		 */
		private function set_auth(&$ch){
			if($this->username !== null && $this->password !== null){
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
				curl_setopt($ch, CURLOPT_USERPWD, $this->username.':'.$this->password);
			}
		}
	}
?>