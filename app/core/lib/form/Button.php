<?php
	namespace Form;
	
	/**
	 * Classe que representa um botão.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 05/02/2014
	*/
	
	class Button extends Field{
		protected $type;
		
		/**
		 * Instancia um novo botão.
		 * 
		 * @param string $name Nome do botão.
		 * @param string $label Rótulo do botão.
		 * @param mixed $value Valor do botão.
		 * @param array $attributes Vetor de atributos do elemento HTML do botão, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param string $type Tipo de botão, que pode ser 'submit', 'image' ou 'button'.
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array(), $type = 'submit'){
			parent::__construct($name, $label, $value, $attributes);
			
			$this->type = in_array($type, array('submit', 'button', 'image')) ? $type : 'submit';
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_language;
			
			if(empty($this->label))
				$this->label = ($this->form->get_mode() == 'edit') ? $sys_language->get('common', 'update') : $sys_language->get('common', 'send_data');
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//HTML
					switch($this->type){
						case 'button':
						case 'submit':
							$this->html = '<button type="'.$this->type.'" value="'.$this->value.'" name="'.$this->name.'" id="'.$this->id.'" '.\UI\HTML::prepare_attr($this->attributes).'>'.$this->label.'</button>';
							break;
						
						case 'image':
							$this->html = '<input type="image" value="'.$this->value.'" name="'.$this->name.'" id="'.$this->id.'" title="'.$this->label.'" '.\UI\HTML::prepare_attr($this->attributes).' />';
							break;
					}
					
					break;
			}
		}
	}
?>