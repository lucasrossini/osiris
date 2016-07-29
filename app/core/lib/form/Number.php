<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo de valor numérico.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 14/02/2014
	*/
	
	class Number extends Field{
		protected $unit;
		protected $decimal;
		protected $negative;
		protected $min;
		protected $max;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param string $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param string $unit Unidade.
		 * @param boolean $decimal Define se deve ser um valor decimal.
		 * @param boolean $negative Define se deve aceitar valores negativos.
		 * @param int $min Valor mínimo.
		 * @param int $max Valor máximo.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $unit = '', $decimal = false, $negative = false, $min = 0, $max = null){
			parent::__construct($name, $label, $value, $attributes);

			$this->unit = $unit;
			$this->decimal = $decimal;
			$this->negative = $negative;
			$this->min = $min;
			$this->max = $max;
			
			if($this->unit)
				$this->label_complement = ' ('.$this->unit.')';
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_assets;
			
			if((string)$this->value !== ''){
				if(!$this->decimal)
					$this->value = (int)str_replace(',', '.', str_replace('.', '', $this->value));

				if(!$this->negative)
					$this->value = abs($this->value);
			}
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//Intervalo de valores
					if(!is_null($this->min))
						$this->attributes['min'] = (int)$this->min;
					
					if(!is_null($this->max))
						$this->attributes['max'] = (int)$this->max;
					
					//HTML
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							<input type="number" name="'.$this->name.'" id="'.$this->id.'" value="'.$this->value.'" autocomplete="off" '.\UI\HTML::prepare_attr($this->attributes).' />
							'.$this->get_tip().'
						</div>
					';
					
					//Script
					$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.numeric.min.js');
					$this->script = '$("input[name=\''.$this->name.'\']").numeric({ decimal: '.\Formatter\String::bool2string($this->decimal).', negative: '.\Formatter\String::bool2string($this->negative).' }).addClass("numeric");';

					break;

				case 'view':
					$unit = $this->unit ? ' '.$this->unit : '';
					$this->html = $this->view($this->value.$unit);
					
					break;
			}
		}
	}
?>