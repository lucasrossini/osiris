<?php
	namespace Google;
	
	/**
	 * Classe para encurtamento de URLs utilizando o serviço Goo.gl.
	 * 
	 * @package Osiris
	 * @author Sebastian Wyder <sebastian.wyder@me.com>
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 09/04/2014
	*/
	
	class Googl{
		private static $api_url = 'https://www.googleapis.com/urlshortener/v1/url';
		private static $api_key = 'AIzaSyAlJv-UdYbo1OC90T53s8e2Ya7Ln1XO0Hs';
		
		private $ch;
		
		/**
		 * Instancia o objeto de encurtamento.
		 */
		public function __construct(){
			$this->ch = curl_init();
			curl_setopt($this->ch, CURLOPT_URL, \URL\URL::add_params(self::$api_url, array('key' => self::$api_key)));
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		
		/**
		 * Encurta uma URL.
		 * 
		 * @param string $url URL a ser encurtada.
		 * @return string URL encurtada.
		 */
		public function shorten($url){
			$data = array('longUrl' => $url);
			
			curl_setopt($this->ch, CURLOPT_POST, sizeof($data));
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			
			return json_decode(curl_exec($this->ch))->id;
		}
		
		/**
		 * Expande (desencurta) uma URL.
		 * 
		 * @param string $url URL encurtada a ser expandida.
		 * @return string URL expandida.
		 */
		public function expand($url){
			curl_setopt($this->ch, CURLOPT_HTTPGET, true);
			curl_setopt($this->ch, CURLOPT_URL, \URL\URL::add_params(self::$api_url, array('key' => self::$api_key, 'shortUrl' => $url)));

			return json_decode(curl_exec($this->ch))->longUrl;
		}
		
		/**
		 * Destrutor.
		 */
		public function __destruct(){
			curl_close($this->ch);
			$this->ch = null;
		}
	}
?>