<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo do tipo checkbox.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 20/03/2014
	*/
	
	class Checkbox extends Field{
		protected $checked;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param string $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param boolean $checked Define se o campo já inicia marcado.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $checked = false){
			parent::__construct($name, $label, $value, $attributes);

			$this->checked = $checked;
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_language;
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//HTML
					$checked_attr = $this->checked ? 'checked' : '';
					$disabled_attr = $this->checked ? 'disabled' : '';
					
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<label class="checkbox">
								<input type="hidden" name="'.$this->name.'" value="" class="hidden-checkbox" '.$disabled_attr.' />
								<input type="checkbox" name="'.$this->name.'" id="'.$this->id.'" value="'.$this->value.'" '.\UI\HTML::prepare_attr($this->attributes).' '.$checked_attr.' />
								'.$this->label.$this->label_complement.'
							</label>
							
							'.$this->get_tip().'
						</div>
					';

					//Script
					$this->script = '
						$("input[name=\''.$this->name.'\']").click(function(){
							if($(this).attr("checked"))
								$(this).siblings("input[type=\'hidden\']").attr("disabled", true);
							else
								$(this).siblings("input[type=\'hidden\']").removeAttr("disabled");
						});
					';
					
					break;

				case 'view':
					$answer = $this->checked ? $sys_language->get('common', '_yes') : $sys_language->get('common', '_no');
					
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<strong>'.$this->label.':</strong> '.$answer.'
						</div>
					';
					
					break;
			}
		}
		
		/**
		 * @see Field::set_value()
		 */
		public function set_value($value, $index = null){
			if(is_array($value))
				$value = $value[$index];
			
			if($value)
				$this->checked = true;
		}
		
		/*-- Validação --*/
		
		/**
		 * @see FieldValidator::is_empty()
		 */
		public function is_empty(){
			global $sys_language;
			
			if(!$this->checked)
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_empty_checkbox'), '<strong rel="'.$this->id.'">'.$this->label.'</strong>'));
			
			return self::valid();
		}
	}
?>