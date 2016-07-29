<?php
	namespace HTTP\REST;
	
	/**
	 * Classe para processamento de requisições REST.
	 * 
	 * @package Osiris
	 * @author Ian Selby
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 21/10/2013
	*/
	
	class REST{
		private $request_vars;
		private $data;
		private $http_accept;
		private $method;
		
		/**
		 * Instancia um objeto de processamento REST.
		 */
		public function __construct(){
			$this->request_vars = array();
			$this->data = '';
			$this->http_accept = (strpos($_SERVER['HTTP_ACCEPT'], 'json')) ? 'json' : 'xml';
			$this->method = 'GET';
		}
		
		/**
		 * Define os dados.
		 * 
		 * @param mixed $data Dados.
		 */
		public function set_data($data){
			$this->data = $data;
		}
		
		/**
		 * Define o método utilizado (GET, POST ou PUT).
		 * 
		 * @param string $method Método utilizado.
		 */
		public function set_method($method){
			$this->method = $method;
		}
		
		/**
		 * Define as variáveis de requisição.
		 * 
		 * @param array $request_vars Vetor de variáveis.
		 */
		public function set_request_vars($request_vars){
			$this->request_vars = $request_vars;
		}
		
		/**
		 * Retorna os dados.
		 * 
		 * @return mixed Dados.
		 */
		public function get_data(){
			return $this->data;
		}
		
		/**
		 * Retorna o método utilizado.
		 * 
		 * @return string Método utilizado.
		 */
		public function get_method(){
			return $this->method;
		}
		
		/**
		 * Retorna o tipo de dados utilizado (JSON ou XML).
		 * 
		 * @return string Tipo de dados utilizado.
		 */
		public function get_http_accept(){
			return $this->http_accept;
		}
		
		/**
		 * Retorna as variáveis de requisição.
		 * 
		 * @return array Vetor de variáveis.
		 */
		public function get_request_vars(){
			return $this->request_vars;
		}
		
		/**
		 * Processa a requisição.
		 * 
		 * @return REST Objeto de processamento REST.
		 */
		public static function process_request(){
			//Método
			$request_method = strtoupper($_SERVER['REQUEST_METHOD']);
			$return_obj = new self();
			
			$data = array();

			switch($request_method){
				case 'GET':
					$data = $_GET;
					break;
				
				case 'POST':
					$data = $_POST;
					break;
				
				case 'PUT':
					parse_str(file_get_contents('php://input'), $put_vars);
					$data = $put_vars;
					
					break;
			}

			//Define o método
			$return_obj->set_method($request_method);

			//Define os dados recebidos
			$return_obj->set_request_vars($data);

			if(isset($data['data']))
				$return_obj->set_data(json_decode($data['data']));
			
			return $return_obj;
		}
		
		/**
		 * Exibe a resposta da requisição.
		 * 
		 * @param int $status Código de status retornado.
		 * @param string $body Corpo da página exibida.
		 * @param string $content_type Tipo de conteúdo da página.
		 */
		public static function send_response($status = 200, $body = '', $content_type = 'text/html'){
			//Define os cabeçalhos HTTP
			header('HTTP/1.1 '.$status.' '.self::get_status_message($status));
			header('Content-type: '.$content_type);

			//Corpo da página de resposta
			if(!empty($body)){
				echo $body;
				exit;
			}
			else{
				$message = '';
				
				//Mensagens de status
				$status_name = self::get_status_message($status);
				
				switch($status){
					case 401:
						$message = 'You must be authorized to view this page.';
						break;
					
					case 404:
						$message = 'The requested URL '.$_SERVER['REQUEST_URI'].' was not found.';
						break;
					
					case 500:
						$message = 'The server encountered an error processing your request.';
						break;
					
					case 501:
						$message = 'The requested method is not implemented.';
						break;
				}

				//Assinatura do servidor
				$signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'].' Server at '.$_SERVER['SERVER_NAME'].' Port '.$_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];
				
				$body = '
					<!DOCTYPE html>
					<html>
						<head>
							<meta charset="utf-8">
							<title>'.$status.' '.$status_name.'</title>
						</head>

						<body>
							<h1>'.$status_name.'</h1>
							<p>'.$message.'</p>
							<hr />
							<address>'.$signature.'</address>
						</body>
					</html>
				';

				echo $body;
				exit;
			}
		}
		
		/**
		 * Retorna a mensagem de um status HTTP.
		 * 
		 * @param int $status Código do status.
		 * @return string Mensagem do status.
		 */
		private static function get_status_message($status){
			$codes = array(
				100 => 'Continue',
				101 => 'Switching Protocols',
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',
				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				306 => '(Unused)',
				307 => 'Temporary Redirect',
				400 => 'Bad Request',
				401 => 'Unauthorized',
				402 => 'Payment Required',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				410 => 'Gone',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported'
			);

			return (isset($codes[$status])) ? $codes[$status] : '';
		}
	}
?>