<?php
	namespace Correios;
	
	/**
	 * Classe para manipulação de objeto para envio dos Correios.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 20/02/2014
	*/
	
	class Item{
		public $weight = 0;
		public $length = 0;
		public $width = 0;
		public $height = 0;
		public $volume = 0;
		
		/**
		 * Instancia um item.
		 */
		public function __construct($weight, $length, $width, $height){
			$this->weight = (float)$weight;
			$this->length = (float)$length;
			$this->width = (float)$width;
			$this->height = (float)$height;
			$this->volume = $this->volume();
		}
		
		/**
		 * Calcula o volume do objeto.
		 * 
		 * @return float Volume do objeto.
		 */
		private function volume(){
			return $this->length * $this->width * $this->height;
		}
	}
?>