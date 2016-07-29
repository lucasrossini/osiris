<?php
	namespace Correios;
	
	/**
	 * Classe para requisição de endereço pelo CEP dos Correios.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 21/02/2014
	*/
	
	abstract class ZipCode{
		/**
		 * Captura o endereço de um CEP.
		 * 
		 * @param string $zip_code CEP a ser consultado.
		 * @param boolean $get_locale_ids Define se o estado e a cidade devem ser retornados como ID dos registros nas tabelas do sistema.
		 * @return array Vetor com os índices 'street', que contém o nome da rua; 'neighborhood', que contém o nome do bairro; 'city', que contém o nome (ou ID) da cidade; e 'state', que contém o nome (ou ID) do estado em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function get_address($zip_code, $get_locale_ids = false){
			$zip_code = trim(str_replace(' ', '', str_replace('-', '', str_replace('.', '', $zip_code))));
			
			$ch = curl_init('http://m.correios.com.br/movel/buscaCepConfirma.do');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'cepEntrada='.$zip_code.'&tipoCep=&cepTemp=&metodo=buscarCep');

			$response = curl_exec($ch);
			curl_close($ch);

			$DOMDocument = new \DOMDocument('1.0', 'UTF-8');
			$DOMDocument->preserveWhiteSpace = false;
			@$DOMDocument->loadHTML($response);
			$DOMXPath = new \DOMXPath($DOMDocument);

			$html = $DOMXPath->query('.//*[@class="respostadestaque"]');
			$values = array();
			
			foreach($html as $content){
				foreach($content->childNodes as $child)
					$values[] = preg_replace('/[\s]{2,}/', null, $child->nodeValue);
			}
			
			if(sizeof($values)){
				$locale_pieces = explode('/', $values[2]);
				$city = $locale_pieces[0];
				$state = strtoupper($locale_pieces[1]);

				if($get_locale_ids){
					global $db;

					//ID do estado
					$db->query('SELECT id FROM sys_state WHERE acronym = "'.$state.'"');
					$state = $db->result(0)->id;

					//ID da cidade
					$db->query('SELECT id FROM sys_city WHERE name LIKE "'.$city.'"');
					$city = $db->result(0)->id;
				}

				return array(
					'street' => reset(explode(' - ', $values[0])),
					'neighborhood' => $values[1],
					'city' => $city,
					'state' => $state
				);
			}
			
			return false;
		}
	}
?>