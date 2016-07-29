<?php
	namespace Correios;
	
	/**
	 * Classe para manipulação de pacotes de objetos para envio dos Correios.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 21/02/2014
	*/
	
	class Package{
		const MIN_LENGTH = 16,
			  MIN_WIDTH = 11,
			  MIN_HEIGHT = 2;
		
		private $items = array();
		private $weight = 0;
		private $length = 0;
		private $width = 0;
		private $height = 0;
		private $volume = 0;
		
		/**
		 * Instancia um pacote.
		 * 
		 * @param array $items Vetor de objetos da classe Item.
		 */
		public function __construct($items = array()){
			foreach($items as $item)
				$this->add_item($item);
		}
		
		/**
		 * Adiciona um objeto ao pacote.
		 * 
		 * @param \Correios\Item $item Objeto adicionado.
		 */
		public function add_item(Item $item){
			$this->items[] = $item;
			$this->calculate_dimensions($item);
		}
		
		/**
		 * Retorna as dimensões do pacote.
		 * 
		 * @return array Vetor de dimensões, com os índices 'weight', que indica o peso; 'length', que indica o comprimento; 'width', que indica a largura; 'height', que indica a altura; e 'volume', que indica o volume.
		 */
		public function get_dimensions(){
			return array(
				'weight' => $this->weight,
				'length' => $this->length,
				'width' => $this->width,
				'height' => $this->height,
				'volume' => $this->volume
			);
		}
		
		/**
		 * Calcula as dimensões do pacote.
		 * 
		 * @param \Correios\Item $item Objeto adicionado.
		 */
		private function calculate_dimensions(Item $item){
			$this->weight += $item->weight;
			$this->volume += $item->volume;
			
			if((int)sizeof($this->items) === 1){
				$this->length = $item->length;
				$this->width = $item->width;
				$this->height = $item->height;
			}
			else{
				$this->length = $this->width = $this->height = ceil(pow($this->volume, 0.333333));
			}
			
			$this->check_dimensions();
		}
		
		/**
		 * Verifica se as dimensões do pacote estão dentro dos limites mínimos.
		 */
		private function check_dimensions(){
			$this->length = $this->min($this->length, self::MIN_LENGTH);
			$this->width = $this->min($this->width, self::MIN_WIDTH);
			$this->height = $this->min($this->height, self::MIN_HEIGHT);
		}
		
		/**
		 * Retorna o valor mínimo entre dois valores.
		 * 
		 * @param mixed $value Valor testado.
		 * @param mixed $min Valor mínimo permitido.
		 * @return mixed Valor mínimo resultante.
		 */
		private function min($value, $min){
			return ($value < $min) ? $min : $value;
		}
	}
?>