<?php
	namespace URL;
	
	/**
	 * Classe para encurtamento de URLs.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 27/02/2014
	*/
	
	abstract class Shortener{
		/**
		 * Encurta uma URL utilizando o goo.gl.
		 * 
		 * @param string $url URL a ser encurtada.
		 * @return string URL encurtada.
		 */
		public static function googl($url){
			$googl = new \Google\Googl();
			return $googl->shorten($url);
		}
		
		/**
		 * Encurta uma URL utilizando o bit.ly.
		 * 
		 * @param string $url URL a ser encurtada.
		 * @return string URL encurtada.
		 */
		public static function bitly($url){
			$bitly = new \URL\Bitly();
			return $bitly->shorten($url);
		}
		
		/**
		 * Encurta uma URL utilizando o migre.me.
		 * 
		 * @param string $url URL a ser encurtada.
		 * @return string URL encurtada.
		 */
		public static function migre_me($url){
			$url = 'http://migre.me/api.xml?url='.$url;
			$text = file_get_contents($url);
			$chars = preg_split('/<*migre>/', $text, -1, PREG_SPLIT_OFFSET_CAPTURE); 
			$shortened = str_replace("</", "", $chars[1][0]);
			
			return $shortened;
		}
	}
?>