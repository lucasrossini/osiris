<?php
	namespace Form;
	
	/**
	 * Classe que representa um grupo de campos do tipo radio.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 21/01/2014
	*/
	
	class RadioGroup extends Field{
		protected $options;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param mixed $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param array $options Vetor com os valores para montagem dos itens, onde a chave é o rótulo do item e o valor é o valor do item.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $options = array()){
			parent::__construct($name, $label, $value, $attributes);

			$this->options = $options;
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			$options = '';
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//HTML
					if(sizeof($this->options)){
						$attributes = \UI\HTML::prepare_attr($this->attributes);

						foreach($this->options as $key => $value){
							$checked_attr = ((string)$this->value === (string)$key) ? 'checked' : '';

							$options .= '
								<label>
									<input type="radio" name="'.$this->name.'" value="'.$key.'" '.$checked_attr.' '.$attributes.' />
									'.$value.'
								</label>
							';
						}
					}
					
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<fieldset class="checkgroup">
								<legend class="label-title">'.$this->label.$this->label_complement.'</legend>
								'.$options.'
							</fieldset>
							
							'.$this->get_tip().'
						</div>
					';
					
					break;
				
				case 'view':
					$checked_option = !empty($this->value) ? $this->options[$this->value] : '';
					$this->html = $this->view($checked_option);
					
					break;
			}
		}
		
		/*-- Validação --*/
		
		/**
		 * @see FieldValidator::is_empty()
		 */
		public function is_empty(){
			global $sys_language;
			
			if(!$this->value)
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_empty_select'), '<strong rel="'.$this->id.'">'.$this->label.'</strong>'));
			
			return self::valid();
		}
	}
?>