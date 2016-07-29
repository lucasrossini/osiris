<?php
	namespace Correios;
	
	/**
	 * Classe para cálculo de frete dos Correios.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 24/02/2014
	*/
	
	abstract class Shipping{
		const PAC = 41106,
			  SEDEX = 40010,
			  SEDEX_A_COBRAR = 40045,
			  SEDEX_10 = 40215,
			  E_SEDEX = 81019;
		
		/**
		 * Realiza o cálculo do frete.
		 * 
		 * @param int $service ID do serviço de envio desejado.
		 * @param string $origin CEP de origem.
		 * @param string $destiny CEP de destino.
		 * @param array $dimensions Vetor com as dimensões do pacote (vide Package::get_dimensions()).
		 * @param float $value Valor declarado dos objetos.
		 * @return array Vetor com o resultado do cálculo que contém os índices 'success', que indica se a consulta foi realizada com sucesso; 'error', que contém a mensagem de erro caso tenha ocorrido; 'value', que indica o valor do frete; e 'delivery_days', que indica o prazo de entrega em dias.
		 */
		public static function calculate($service, $origin, $destiny, $dimensions, $value){
			//Retira formatação dos CEPs
			$origin = str_replace('-', '', $origin);
			$destiny = str_replace('-', '', $destiny);
			
			//Monta os parâmetros da requisição
			$params = array(
				'nCdServico' => $service,
				'sCepOrigem' => $origin,
				'sCepDestino' => $destiny,
				'nVlPeso' => $dimensions['weight'],
				'nVlComprimento' => $dimensions['length'],
				'nVlLargura' => $dimensions['width'],
				'nVlAltura' => $dimensions['height'],
				'nVlValorDeclarado' => (float)$value,
				'nCdFormato' => 1,
				'sCdMaoPropria' => 'n',
				'sCdAvisoRecebimento' => 'n',
				'nVlDiametro' => 0,
				'StrRetorno' => 'xml',
				'nCdEmpresa' => '',
				'sDsSenha' => ''
			);
			
			//Faz a chamada ao webservice
			$xml = self::call_webservice($params);
			$result = \XML\XML::parse($xml);
			
			//Retorna o resultado
			if((int)$result['cServico']['Erro'] !== 0)
				return array('success' => false, 'error' => $result['cServico']['MsgErro']);
			else
				return array('success' => true, 'value' => (float)str_replace(',', '.', $result['cServico']['Valor']), 'delivery_days' => (int)$result['cServico']['PrazoEntrega']);
		}
		
		/**
		 * Faz a chamada ao webservice dos Correios.
		 * 
		 * @param array $params Vetor de parâmetros para cálculo do frete.
		 * @return string XML com o resultado do cálculo.
		 */
		private static function call_webservice($params = array()){
			$url = \URL\URL::add_params('http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx', $params);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			
			ob_start();
			curl_exec($ch);
			$response = ob_get_contents();
			ob_end_clean();
			
			return $response;
		}
		
		/**
		 * Retorna o nome do serviço de entrega.
		 * 
		 * @param int $service Código do serviço.
		 * @return string Nome do serviço.
		 */
		public static function get_name($service){
			switch($service){
				case self::PAC: return 'PAC';
				case self::SEDEX: return 'SEDEX';
				case self::SEDEX_10: return 'SEDEX 10';
				case self::SEDEX_A_COBRAR: return 'SEDEX a cobrar';
				case self::E_SEDEX: return 'e-SEDEX';
				default: return '';
			}
		}
	}
?>