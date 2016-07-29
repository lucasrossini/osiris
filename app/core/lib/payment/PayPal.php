<?php
	namespace Payment;
	
	/**
	 * Classe para realização de pagamentos através do PayPal.
	 * 
	 * @package Osiris
	 * @author João Batista Neto <neto.joaobatista@gmail.com>
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 31/07/2013
	 */
	
	abstract class PayPal{
		/**
		 * Realiza o checkout.
		 * 
		 * @param array $product_info Vetor contendo informações do produto a ser comprado, com os campos 'id', que indica o identificador do produto; 'name', que indica o nome do produto; 'price', que indica o preço do produto; e 'description', que indica a descrição do produto.
		 * @param string $return_url URL (relativa ao site) de retorno após o pagamento.
		 * @param string $cancel_url URL (relativa ao site) de retorno após o cancelamento.
		 * @param boolean $sandbox Define se deve trabalhar em modo 'sandbox'.
		 * @return boolean FALSE caso houver falha na transação.
		 */
		public static function checkout($product_info, $return_url = '/', $cancel_url = '/', $sandbox = false){
			$conf_paypal = new \System\Config('paypal');
			$payment_url = $sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
			
			$nvp = array(
				'METHOD' => 'SetExpressCheckout',
				'VERSION' => '93',
				'PWD' => $conf_paypal->get('password'),
				'USER' => $conf_paypal->get('user'),
				'SIGNATURE' => $conf_paypal->get('signature'),
				'PAYMENTREQUEST_0_AMT' => $product_info['price'],
				'PAYMENTREQUEST_0_ITEMAMT' => $product_info['price'],
				'PAYMENTREQUEST_0_CURRENCYCODE' => 'BRL',
				'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
				'L_PAYMENTREQUEST_0_NUMBER0' => $product_info['id'],
				'L_PAYMENTREQUEST_0_NAME0' => $product_info['name'],
				'L_PAYMENTREQUEST_0_QTY0' => 1,
				'L_PAYMENTREQUEST_0_AMT0' => $product_info['price'],
				'L_PAYMENTREQUEST_0_DESC0' => $product_info['description'],
				'RETURNURL' => BASE.$return_url,
				'CANCELURL' => BASE.$cancel_url,
				'LOCALECODE' => 'BR',
				'CURRENCYCODE' => 'BRL',
				'ALLOWNOTE' => 0,
				'NOSHIPPING' => 1
			);

			$response_nvp = self::api_call($nvp, $sandbox);

			if(isset($response_nvp['ACK']) && ($response_nvp['ACK'] == 'Success')){
				$query = array(
					'cmd' => '_express-checkout',
					'token' => $response_nvp['TOKEN']
				);
				
				\URL\URL::redirect($payment_url.'?'.http_build_query($query));
			}
			else{
				return false;
			}
		}
		
		/**
		 * Recebe uma notificação e confirma o pagamento.
		 * 
		 * @param boolean $sandbox Define se deve trabalhar em modo 'sandbox'.
		 * @return array Vetor com os índices 'status', que indica o estado da transação, podendo conter 'success' ou 'error'; e 'message', que contém a mensagem de retorno de acordo com o estado da transação.
		 */
		public static function confirm_payment($sandbox = false){
			global $sys_language;
			$response = array('status' => '', 'message' => '');
			
			if(\HTTP\Request::is_set('get', 'token')){
				$token = \HTTP\Request::get('token');
				$conf_paypal = new \System\Config('paypal');

				$nvp = array(
					'TOKEN' => $token,
					'METHOD' => 'GetExpressCheckoutDetails',
					'VERSION' => '93',
					'PWD' => $conf_paypal->get('password'),
					'USER' => $conf_paypal->get('user'),
					'SIGNATURE' => $conf_paypal->get('signature')
				);

				$response_nvp = self::api_call($nvp, $sandbox);

				if(isset($response_nvp['TOKEN'], $response_nvp['ACK'])){
					if(($response_nvp['TOKEN'] == $token) && ($response_nvp['ACK'] == 'Success')){
						$nvp['METHOD'] = 'DoExpressCheckoutPayment';
						$nvp['PAYERID'] = $response_nvp['PAYERID'];
						$nvp['PAYMENTREQUEST_0_AMT'] = $response_nvp['PAYMENTREQUEST_0_AMT'];
						$nvp['PAYMENTREQUEST_0_CURRENCYCODE'] = $response_nvp['PAYMENTREQUEST_0_CURRENCYCODE'];
						$nvp['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Sale';

						$response_nvp = self::api_call($nvp, $sandbox);

						if($response_nvp['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Completed'){
							$response['status'] = 'success';
							$response['message'] = $sys_language->get('class_paypal', 'transaction_success');
						}
						else{
							$response['status'] = 'error';
							$response['message'] = $sys_language->get('class_paypal', 'transaction_error');
						}
					}
					else{
						$response['status'] = 'error';
						$response['message'] = $sys_language->get('class_paypal', 'transaction_error');
					}
				}
				else{
					$response['status'] = 'error';
					$response['message'] = $sys_language->get('class_paypal', 'transaction_error');
				}
			}
			
			return $response;
		}
		
		/**
		 * Faz uma requisição à API.
		 * 
		 * @param array $nvp Vetor contendo os parâmetros necessários para a chamada.
		 * @param boolean $sandbox Define se deve trabalhar em modo 'sandbox'.
		 * @return array Vetor com os dados de resposta.
		 */
		private static function api_call($nvp = array(), $sandbox = false){
			$api_url = $sandbox ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';
			$curl = curl_init();

			curl_setopt($curl, CURLOPT_URL, $api_url);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($nvp));

			$response = urldecode(curl_exec($curl));
			$response_nvp = array();

			curl_close($curl);
			
			if(preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $response, $matches)){
				foreach($matches['name'] as $offset => $name)
					$response_nvp[$name] = $matches['value'][$offset];
			}
			
			return $response_nvp;
		}
	}
?>