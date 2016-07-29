<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo de valor monetário.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 12/02/2014
	*/
	
	class Money extends Field{
		protected $unit;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param string $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param array $unit Vetor de informações da unidade monetária, com os índices 'acronym', que indica a sigla da unidade; e 'name', que indica o nome da moeda.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $unit = array('acronym' => 'R$', 'name' => 'Real')){
			parent::__construct($name, $label, $value, $attributes);
			
			$this->unit = $unit;
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_assets;
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//HTML
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							
							<span class="money-input-container">
								<input type="text" name="'.$this->name.'" id="'.$this->id.'" value="'.$this->value.'" '.\UI\HTML::prepare_attr($this->attributes).' />
								<abbr class="unit" title="'.$this->unit['name'].'">'.$this->unit['acronym'].'</abbr>
							</span>
							
							'.$this->get_tip().'
						</div>
					';

					//Script
					$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.maskMoney.min.js');
					$this->script = '$("input[name=\''.$this->name.'\']").maskMoney({decimal: ",", thousands: "."});';

					break;

				case 'view':
					$this->html = $this->view($this->unit['acronym'].' '.$this->value);
					break;
			}
		}
	}
?>