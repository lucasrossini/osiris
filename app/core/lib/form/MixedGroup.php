<?php
	namespace Form;
	
	/**
	 * Classe que representa um grupo de campos de tipos diversos.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 28/02/2014
	*/
	
	class MixedGroup extends Field{
		protected $items;
		protected $items_count;
		protected $max_items;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param array $value Vetor multidimensional com as opções selecionadas de cada campo da linha.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param array $items Vetor de objetos dos campos (classe Field) da linha.
		 * @param int $items_count Quantidade de campos a serem exibidos inicialmente.
		 * @param int $max_items Quantidade máxima de campos (0 para ilimitado).
		 */
		public function __construct($name, $label = '', $value = array(), $attributes = array(), $items = array(), $items_count = 1, $max_items = 0){
			parent::__construct($name, $label, $value, $attributes);
			
			$this->items = $items;
			$this->max_items = (int)$max_items;
			
			$value_size = sizeof($this->value);
			$this->items_count = $value_size ? $value_size : (int)$items_count;
			
			if(!$this->items_count)
				$this->items_count = 1;
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
					$this->html .= '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label multiple-items-container no-focus">
							<fieldset class="fieldgroup mixedgroup" id="'.$this->id.'-mixedgroup">
								<legend class="label-title">'.$this->label.$this->label_complement.'</legend>
					';
					
					if($this->max_items)
						$this->html .= '<span class="info">* '.sprintf($sys_language->get('class_form', 'max_items'), $this->max_items).'</span>';
					
					$fields_count = sizeof($this->items);
					
					for($i = 1; $i <= $this->items_count; $i++){
						$this->html .= '<div class="inline-labels grid-'.$fields_count.'">';
						
						foreach($this->items as $field){
							//Clona o objeto
							$field = clone $field;
							
							//Atribui os valores ao objeto
							$field_id = $field->get('id');
							$field->set('form', $this->form);
							
							if($this->form->is_submitted() || in_array($this->form->get_mode(), array('edit', 'view')))
								$field->set_value($this->form->get($field_id), $i - 1);
							
							//Adiciona o campo à lista de campos do formulário
							$this->form->add_to_list($field);
							
							//Exibe o conteúdo do campo
							$field->render();
							$this->html .= $field->get('html');
							$this->script .= $field->get('script');
						}
						
						$this->html .= '
								<a href="#" class="remove-item" title="'.$sys_language->get('class_form', 'remove_item').'"><img src="app/assets/images/delete.png" alt="'.$sys_language->get('class_form', 'remove_item').'" /></a>
							</div>
						';
					}
					
					$this->html .= '
							</fieldset>
							
							<a href="#" class="icon add">'.$sys_language->get('class_form', 'add_item').'</a>
							'.$this->get_tip().'
						</div>
					';
					
					//Script
					$this->script .= '
						//Objeto mixedgroup
						var '.$this->id.'_mixedgroup = {
							container: $("#'.$this->id.'-mixedgroup"),
							items_count: '.$this->items_count.',
							max_items: '.$this->max_items.',
							
							//Adiciona um item
							add_item: function(){
								if((this.items_count < this.max_items) || !this.max_items){
									this.items_count++;
									item_clone = this.container.find(".inline-labels:first").clone(true).find("input, select").val("").end().hide();
									this.container.append(item_clone).find(".inline-labels").fadeIn(200);
								}
								else{
									alert("'.sprintf($sys_language->get('class_form', 'max_items_message'), '" + this.max_items + "').'");
								}
							},
							
							//Remove um item
							remove_item: function(element){
								var self = this;
								
								if(this.items_count > 1){
									element.remove();
									self.items_count--;
								}
								else{
									alert("'.$sys_language->get('class_form', 'min_items_message').'");
								}
							}
						};
						
						$("#'.$this->id.'-mixedgroup .remove-item").live("click", function(){
							'.$this->id.'_mixedgroup.remove_item($(this).parent());
							return false;
						});
						
						$("#label-'.$this->id.' .add").click(function(){
							'.$this->id.'_mixedgroup.add_item();
							return false;
						});
						
						$(document).ready(function(){
							$("#'.$this->id.'-mixedgroup").find("select").trigger("change");
						});
					';
					
					break;

				case 'view':
					$content = '';
					$fields_count = sizeof($this->items);
					
					for($i = 1; $i <= $this->items_count; $i++){
						$content .= '<div class="inline-labels grid-'.$fields_count.'">';
						
						foreach($this->items as $field){
							//Clona o objeto
							$field = clone $field;
							
							//Atribui os valores ao objeto
							$field_id = $field->get('id');
							$field->set('form', $this->form);
							
							//Exibe o conteúdo do campo
							$field->set_value($this->form->get($field_id), $i - 1);
							$field->render();
							$content .= $field->get('html');
						}
						
						$content .= '</div>';
					}
					
					$this->html .= $this->view($content);
					break;
			}
		}
		
		/**
		 * @see Field::set_value()
		 */
		public function set_value($value = '', $index = null){
			$this->items_count = sizeof($this->form->get(reset($this->items)->get('id')));
			
			if(!$this->items_count)
				$this->items_count = 1;
		}
	}
?>