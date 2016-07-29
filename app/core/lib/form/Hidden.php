<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo oculto.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 27/01/2014
	*/
	
	class Hidden extends Field{
		/**
		 * @see Field::__construct()
		 */
		public function __construct($name, $label = '', $value = '', $attributes = array()){
			parent::__construct($name, $label, $value, $attributes);
		}
		
		/**
		 * @see Field::render()
		 */
		public function render(){
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					$this->html = '<input type="hidden" name="'.$this->name.'" id="'.$this->id.'" value="'.$this->value.'" '.\UI\HTML::prepare_attr($this->attributes).' />';
					break;
			}
		}
	}
?>