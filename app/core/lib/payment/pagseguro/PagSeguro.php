<?php
	namespace Payment\PagSeguro;
	
	/**
	 * Classe para realização de pagamentos através do PagSeguro.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 26/03/2014
	 */
	
	class PagSeguro{
		const API_LIB = '/app/core/lib/payment/pagseguro/api/PagSeguroLibrary.php';
		const LOG_DIR = '/app/core/lib/payment/pagseguro/log/';
		const LOG_FILE = 'error.log';
		
		private $credentials;
		
		/**
		 * Instancia um objeto do PagSeguro.
		 * 
		 * @param string $user Usuário da conta.
		 * @param string $token Token de segurança.
		 */
		public function __construct($user = '', $token = ''){
			//Carrega as configurações do PagSeguro
			$conf_pagseguro = new \System\Config('pagseguro');
			
			//Inclui a API do PagSeguro
			require_once ROOT.self::API_LIB;
			
			//Registra as credenciais
			$user = empty($user) ? $conf_pagseguro->get('user') : $user;
			$token = empty($token) ? $conf_pagseguro->get('token') : $token;
			
			$this->credentials = new \PagSeguroAccountCredentials($user, $token);
		}
		
		/**
		 * Realiza o checkout.
		 * 
		 * @param int $id Identificador da venda.
		 * @param array $products Vetor multidimensional contendo informações dos produtos a serem comprados, com os campos 'id', que indica o identificador do produto; 'name', que indica o nome do produto; 'price', que indica o preço do produto; e 'quantity', que indica a quantidade de itens do produto.
		 * @param array $buyer_info Vetor contendo as informações sobre o comprador, com os campos 'name', que indica o nome; 'email', que indica o endereço de e-mail; e 'phone', que indica o telefone.
		 * @param array $shipping_info Vetor contendo as informações sobre o envio dos produtos, com os campos 'type', que indica o tipo de envio dentre PAC, SEDEX ou NOT_SPECIFIED; 'cost', que indica o valor do frete; 'cep', que indica o CEP; 'street', que indica a rua; 'number', que indica o número do local; 'complement', que indica o complemento; 'neighborhood', que indica o bairro; 'city', que indica a cidade; e 'state', que indica a sigla do estado.
		 * @param string $return_url URL (relativa ao site) de retorno após o pagamento.
		 * @param boolean $redirect Define se deve ser redirecionado para a página de pagamento do PagSeguro.
		 * @return string URL de pagamento do PagSeguro.
		 */
		public function checkout($id, $products = array(), $buyer_info = array(), $shipping_info = array('type' => 'NOT_SPECIFIED', 'cost' => 0), $return_url = '/checkout', $redirect = true){
			$request = new \PagSeguroPaymentRequest();
			
			$request->setCurrency('BRL');
			$request->setReference($id);
			$request->setRedirectUrl(BASE.$return_url);
			
			//Adiciona os produtos à requisição
			if(is_array($products) && sizeof($products)){
				foreach($products as $product){
					$quantity = !$product['quantity'] ? 1 : (int)$product['quantity'];
					$request->addItem($product['id'], $product['name'], $quantity, number_format($product['price'], 2, '.', ''));
				}
			}
			
			self::log(print_r($products, true));
			
			//Adiciona informações do comprador
			if(is_array($buyer_info) && sizeof($buyer_info)){
				$phone_area_code = reset(\Util\Regex::extract_parenthesis($buyer_info['phone']));
				$phone_number = str_replace('-', '', end(explode(')', $buyer_info['phone'])));
				
				$request->setSender(utf8_decode($buyer_info['name']), $buyer_info['email'], $phone_area_code, $phone_number);
			}
			
			//Adiciona informações de envio
			if(is_array($shipping_info) && sizeof($shipping_info)){
				$request->setShippingType(\PagSeguroShippingType::getCodeByType(strtoupper($shipping_info['type'])));
				$request->setShippingAddress(str_replace('-', '', $shipping_info['cep']), $shipping_info['street'], $shipping_info['number'], $shipping_info['complement'], $shipping_info['neighborhood'], $shipping_info['city'], $shipping_info['state'], 'BRA');
				$request->setShippingCost(number_format($shipping_info['cost'], 2, '.', ''));
			}
			
			//Registra a requisição
			try{
				$payment_url = $request->register($this->credentials);
			}
			catch(\PagSeguroServiceException $e){
				die($e->getMessage());
			}
			
			//URL de pagamento
			if($redirect)
				\URL\URL::redirect($payment_url);
			
			return $payment_url;
		}
		
		/**
		 * Recebe uma notificação.
		 * 
		 * @return array Vetor com as informações da transação notificada.
		 */
		public function listen_notification(){
			$code = (isset($_POST['notificationCode']) && trim($_POST['notificationCode']) !== '' ? trim($_POST['notificationCode']) : null);
			$type = (isset($_POST['notificationType']) && trim($_POST['notificationType']) !== '' ? trim($_POST['notificationType']) : null);
			$transaction = array();

			if($code && $type){
				$notification_type = new \PagSeguroNotificationType($type);
				$type_name = $notification_type->getTypeFromValue();

				switch($type_name){
					case 'TRANSACTION':
						$transaction = self::get_transaction_data(self::get_transaction_object($code));
						break;

					default:
						self::log('Tipo de notificação desconhecida: ['.$notification_type->getValue().']');
						return false;
				}
			}
			else{
				self::log('Parâmetros de notificação inválidos!');
			}
			
			return $transaction;
		}
		
		/**
		 * Retorna um objeto de transação.
		 * 
		 * @param string $code Código da notificação.
		 * @return object Objeto da transação.
		 */
		private function get_transaction_object($code){
			try{
				$transaction = \PagSeguroNotificationService::checkTransaction($this->credentials, $code);
			}
			catch(\PagSeguroServiceException $e){
				die($e->getMessage());
			}
			
			return $transaction;
		}
		
		/**
		 * Retorna as informações principais de uma transação.
		 * 
		 * @param object $transaction Objeto da transação.
		 * @return array|boolean Vetor com as informações da transação ou FALSE caso a transação seja inválida.
		 */
		private static function get_transaction_data($transaction){
			if($transaction){
				//Status da transação
				switch($transaction->getStatus()->getTypeFromValue()){
					case 'CANCELLED':
					case 'REFUNDED':
						$status = 'cancelled';
						break;

					case 'PAID':
						$status = 'confirmed';
						break;

					default:
						$status = '';
				}
				
				//Forma de pagamento
				switch($transaction->getPaymentMethod()->getType()->getTypeFromValue()){
					case 'CREDIT_CARD':
						$method = 'Cartão de crédito';
						break;

					case 'BOLETO':
						$method = 'Boleto bancário';
						break;
					
					case 'ONLINE_TRANSFER':
						$method = 'Transferência online';
						break;
					
					case 'BALANCE':
						$method = 'Saldo PagSeguro';
						break;
					
					case 'OI_PAGGO':
						$method = 'Oi Paggo';
						break;

					default:
						$method = '';
				}
				
				//Data/hora da transação
				$datetime_pieces = explode('T', $transaction->getDate());
				$date = \DateTime\Date::convert($datetime_pieces[0]);
				$time = reset(explode('.', $datetime_pieces[1]));
				
				//Dados da transação
				$data = array(
					'id' => $transaction->getReference(),
					'status' => $status,
					'date' => $date,
					'time' => $time,
					'method' => $method,
					'items' => $transaction->getItems()
				);
				
				return $data;
			}
			
			return false;
		}
		
		/**
		 * Registra as transações no log.
		 * 
		 * @param string $content Conteúdo a ser registrado.
		 */
		private static function log($content){
			\Storage\File::put(self::LOG_DIR, self::LOG_FILE, $content."\n", true);
		}
	}
?>