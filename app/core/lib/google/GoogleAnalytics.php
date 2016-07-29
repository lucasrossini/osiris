<?php
	namespace Google;
	
	/**
	 * Classe para captura de dados do Google Analytics.
	 * 
	 * @package Osiris
	 * @author Doug Tan
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 07/03/2014
	*/
 
	class GoogleAnalytics{
		private $_email;
		private $_passwd;
		private $_authCode;
		private $_profileId;
		private $_endDate;
		private $_startDate;
		
		/**
		 * Instancia um objeto do Google Analytics.
		 * 
		 * @param string $email E-mail da conta Google.
		 * @param string $passwd Senha da conta Google.
		 * @throws Exception Falha de autenticação.
		 */
		public function __construct($email, $passwd){
			$this->_email = $email;
			$this->_passwd = $passwd;
			
			$this->_endDate = date('Y-m-d', mktime(0, 0, 0, date("m") , date("d") - 1, date("Y")));
			$this->_startDate = date('Y-m-d', mktime(0, 0, 0, date("m") , date("d") - 31, date("Y")));
			
			if(isset($this->_email) && isset($this->_passwd)){
				if(!$this->_authenticate()){
					throw new \Exception('Failed to authenticate, please check your email and password.');
				}
			}
		}
		
		/**
		 * Define o perfil.
		 * 
		 * @param string $id ID do perfil do Google Analytics.
		 * @throws Exception Perfil inválido.
		 * @return boolean TRUE em caso de sucesso.
		 */
		public function setProfile($id) {
			if(!preg_match('/^ga:\d{1,10}/', $id)){
				throw new \Exception('Invalid GA Profile ID set. The format should ga:XXXXXX, where XXXXXX is your profile number');
			}
			
			$this->_profileId = $id;
			return true;
		}
		
		/**
		 * Define o intervalo de datas.
		 * 
		 * @param string $startDate Data de início.
		 * @param string $endDate Data de término.
		 * @throws Exception Formato de data inválido ou intervalo inválido.
		 * @return boolean TRUE em caso de sucesso.
		 */
		public function setDateRange($startDate, $endDate){
			if(!preg_match('/\d{4}-\d{2}-\d{2}/', $startDate)){
				throw new \Exception('Format for start date is wrong, expecting YYYY-MM-DD format');
			}
			if(!preg_match('/\d{4}-\d{2}-\d{2}/', $endDate)){
				throw new \Exception('Format for end date is wrong, expecting YYYY-MM-DD format');
			}
			if(strtotime($startDate)>strtotime($endDate)){
				throw new \Exception('Invalid Date Range. Start Date is greated than End Date');
			}
			
			$this->_startDate = $startDate;
			$this->_endDate = $endDate;
			
			return true;
		}
		
		/**
		 * Carrega os dados do relatório.
		 * 
		 * @param array $properties Vetor com os parâmetros GET a serem passados para a chamada à API, onde a chave é o nome do parâmetro e o valor é o valor do parâmetro.
		 * @throws Exception Falha na chamada à API.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function getReport($properties = array()){
			if(!count($properties)){
				die('getReport requires valid parameter to be passed');
				return false;
			}
			
			foreach($properties as $key => $value){
				$params[] = $key.'='.$value;
			}
			
			$apiUrl = 'https://www.google.com/analytics/feeds/data?ids='.$this->_profileId.'&start-date='.$this->_startDate.'&end-date='.$this->_endDate.'&'.implode('&', $params);
			$xml = $this->_callAPI($apiUrl);
			
			if($xml){
				$dom = new DOMDocument();
				$dom->loadXML($xml);
				$entries = $dom->getElementsByTagName('entry');
				
				foreach($entries as $entry){
					$dimensions = $entry->getElementsByTagName('dimension');
					foreach($dimensions as $dimension){
						$dims .= $dimension->getAttribute('value').'~~';
					}
	
					$metrics = $entry->getElementsByTagName('metric');
					foreach($metrics as $metric){
						$name = $metric->getAttribute('name');
						$mets[$name] = $metric->getAttribute('value');
					}
					
					$dims = trim($dims, '~~');
					$results[$dims] = $mets;
					
					$dims = '';
					$mets = '';
				}
			}
			else{
				throw new \Exception('getReport() failed to get a valid XML from Google Analytics API service');
			}
			
			return $results;
		}
		
		/**
		 * Carrega os perfis da conta.
		 * 
		 * @throws Exception Falha na chamada à API.
		 * @return array Vetor com os perfis resultantes.
		 */
		public function getWebsiteProfiles(){
			$response = $this->_callAPI('https://www.google.com/analytics/feeds/accounts/default');
			
			if($response){
				$dom = new DOMDocument();
				$dom->loadXML($response);
				$entries = $dom->getElementsByTagName('entry');
				
				foreach($entries as $entry){
					$tmp['title'] = $entry->getElementsByTagName('title')->item(0)->nodeValue;
					$tmp['id'] = $entry->getElementsByTagName('id')->item(0)->nodeValue;
					
					foreach($entry->getElementsByTagName('property') as $property){
						if(strcmp($property->getAttribute('name'), 'ga:accountId') == 0){
							$tmp["accountId"] = $property->getAttribute('value');
						}
						if(strcmp($property->getAttribute('name'), 'ga:accountName') == 0){
						   $tmp["accountName"] = $property->getAttribute('value');
						}
						if(strcmp($property->getAttribute('name'), 'ga:profileId') == 0){
							$tmp["profileId"] = $property->getAttribute('value');
						}
						if(strcmp($property->getAttribute('name'), 'ga:webPropertyId') == 0){
							$tmp["webProfileId"] = $property->getAttribute('value');
						}
					}
					$profiles[] = $tmp;
				}
			}
			else{
				throw new \Exception('getWebsiteProfiles() failed to get a valid XML from Google Analytics API service');
			}
			
			return $profiles;
		}
		
		/**
		 * Faz uma chamada à API.
		 * 
		 * @param string $url Endereço da API.
		 */
		private function _callAPI($url){
			return $this->_postTo($url, array(), array("Authorization: GoogleLogin auth=".$this->_authCode));
		}
			
		/**
		 * Autentica a conta e carrega o código de autenticação.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		private function _authenticate(){
			$postdata = array(
				'accountType' => 'GOOGLE',
				'Email' => $this->_email,
				'Passwd' => $this->_passwd,
				'service' => 'analytics',
				'source' => 'askaboutphp-v01'
			);
			
			$response = $this->_postTo("https://www.google.com/accounts/ClientLogin", $postdata);
			
			if($response){
				preg_match('/Auth=(.*)/', $response, $matches);
				
				if(isset($matches[1])){
					$this->_authCode = $matches[1];
					return true;
				}
			}
			
			return false;
		}
			
		/**
		 * Faz uma chamada cURL.
		 * 
		 * @param string $url Endereço a ser chamado.
		 * @param array $data Vetor com os parâmetros a serem passados pela solicitação.
		 * @param array $header Cabeçalhos a serem passados pela solicitação.
		 * @throws Exception Falha na solicitação.
		 * @return string|boolean Resultado da solicitação em caso de sucesso ou FALSE em caso de falha.
		 */
		private function _postTo($url, $data = array(), $header = array()){
			if(!isset($url))
				return false;
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			
			if(count($data) > 0){
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			else{
				$header[] = array("application/x-www-form-urlencoded");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			}
			
			$response = curl_exec($ch);
			$info = curl_getinfo($ch);
			
			curl_close($ch);
			
			switch($info['http_code']){
				case 200:
					return $response;
				
				case 400:
					throw new \Exception('Bad request - '.$response);
					break;
				
				case 401:
					throw new \Exception('Permission Denied - '.$response);
					break;
				
				default:
					return false;
			}
		}
		
		/**
		 * Monta script do Google Analytics.
		 * 
		 * @return string Script.
		 */
		public static function get_script($id = ''){
			$script = '';
			
			if(empty($id) && defined('ANALYTICS_ID'))
				$id = ANALYTICS_ID;
			
			if(!empty($id) && !\HTTP\Server::is_local()){
				$script = '
					<script>
						//Google Analytics
						var _gaq = _gaq || [];
						_gaq.push(["_setAccount", "UA-'.$id.'"]);
						_gaq.push(["_trackPageview"]);
						_gaq.push(["_trackPageLoadTime"]);
						
						(function(){
							var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true;
							ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js";
							var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s);
						})();
					</script>
				';
			}
			
			return $script;
		}
	}
?>