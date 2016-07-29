<?php
	namespace Form;
	
	/**
	 * Classe que representa um grupo de campos de texto.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 20/02/2014
	*/
	
	class SelectGroup extends Field{
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
		 * @param array $items Vetor de itens do grupo, onde a chave é o nome de cada campo da linha e o valor é um vetor com os índices 'label', que contém o rótulo do campo; e 'options', que contém um vetor com as opções do campo.
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
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label multiple-items-container no-focus">
							<fieldset class="fieldgroup selectgroup" id="'.$this->id.'-selectgroup">
								<legend class="label-title">'.$this->label.$this->label_complement.'</legend>
					';
					
					if($this->max_items)
						$this->html .= '<span class="info">* '.sprintf($sys_language->get('class_form', 'max_items'), $this->max_items).'</span>';
					
					$fields_count = sizeof($this->items);
					
					for($i = 1; $i <= $this->items_count; $i++){
						$this->html .= '<div class="inline-labels grid-'.$fields_count.'">';
						$j = 0;
						
						foreach($this->items as $item_name => $item_attr){
							$options = '';
							$current_value = $this->value[$i - 1][$j];
							
							foreach($item_attr['options'] as $key => $value){
								$selected = ((string)$current_value === (string)$key) ? 'selected' : '';
								$options .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
							}

							$this->html .= '
								<label class="label">
									<span class="label-title">'.$item_attr['label'].' <span class="item-counter">'.$i.'</span></span>
									<select name="'.$item_name.'[]" id="'.$item_name.'-item-'.$i.'" data-value="'.$current_value.'" data-id="'.$item_name.'">'.$options.'</select>
								</label>
							';
							
							$j++;
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
					$select_fields = array();
					
					foreach($this->items as $item_name => $item_attr){
						$select_fields[$item_name] = array();
						
						foreach($item_attr['options'] as $option_id => $option_value)
							$select_fields[$item_name]['_'.$option_id] = $option_value;
					}
					
					$this->script = '
						//Objeto selectgroup
						var '.$this->id.'_selectgroup = {
							container: $("#'.$this->id.'-selectgroup"),
							items_count: '.$this->items_count.',
							max_items: '.$this->max_items.',
							select_fields: '.json_encode($select_fields).',
							
							//Adiciona um item
							add_item: function(){
								if((this.items_count < this.max_items) || !this.max_items){
									this.items_count++;
									item_clone = this.container.find(".inline-labels:first").clone().find("select option").remove().end().hide();
									
									$.each(this.select_fields, function(select_name, select_options){
										var current_select_field = item_clone.find("select[name=\'" + select_name + "[]\']")
										
										$.each(select_options, function(option_id, option_value){
											current_select_field.append("<option value=\'" + option_id.replace("_", "") + "\'>" + option_value + "</option>");
										});
									});
									
									this.container.append(item_clone).find(".inline-labels").fadeIn(200);
									this.update_items_id();
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
									self.update_items_id();
								}
								else{
									alert("'.$sys_language->get('class_form', 'min_items_message').'");
								}
							},
							
							//Ajusta a numeração dos itens da lista
							update_items_id: function(){
								for(var i = 1; i <= this.items_count; i++){
									this.container.find(".inline-labels:eq(" + (i - 1) + ") .item-counter").text(i).parents(".inline-labels:first").find("select").attr("id", function(index, attr){
										return $(this).data("id") + "-item-" + i;
									});
								}
							}
						};
						
						$("#'.$this->id.'-selectgroup .remove-item").live("click", function(){
							'.$this->id.'_selectgroup.remove_item($(this).parent());
							return false;
						});
						
						$("#label-'.$this->id.' .add").click(function(){
							'.$this->id.'_selectgroup.add_item();
							return false;
						});
						
						$(document).ready(function(){
							$("#'.$this->id.'-selectgroup").find("select").trigger("change");
						});
					';
					
					break;

				case 'view':
					$content = '';
					
					if(sizeof($this->value)){
						for($i = 0; $i < $this->items_count; $i++){
							$content .= '<ul>';
							$j = 0;
							
							foreach($this->items as $item_attr)
								$content .= '<li><strong>'.$item_attr['label'].' '.($i + 1).':</strong> '.$item_attr['options'][$this->value[$i][$j++]].'</li>';
							
							$content .= '</ul>';
						}
					}
					
					$this->html = $this->view($content);
					break;
			}
		}
		
		/**
		 * @see Field::set_value()
		 */
		public function set_value($value, $index = null){
			$select_fields = array_keys($this->items);
			$items_count = sizeof($this->form->get(reset($select_fields)));
			$value = array();
			
			for($i = 0; $i < $items_count; $i++){
				foreach($select_fields as $select_field)
					$value[$i][] = $this->form->get($select_field, '', $i);
			}
			
			$this->items_count = $items_count;
			$this->value = $value;
			
			if(!$this->items_count)
				$this->items_count = 1;
		}
		
		/*-- Validação --*/
		
		/**
		 * @see FieldValidator::is_empty()
		 */
		public function is_empty(){
			global $sys_language;
			$select_fields = array_keys($this->items);
			
			foreach($select_fields as $select_field){
				$value = $this->form->get($select_field);
				
				if(!\Util\ArrayUtil::is_empty($value))
					return self::valid();
			}
			
			return self::invalid(sprintf($sys_language->get('class_form', 'validation_empty_multi_select'), '<strong rel="'.$this->id.'">'.$this->label.'</strong>'));
		}
		
		/**
		 * @see FieldValidator::has_repeated_entries()
		 */
		public function has_repeated_entries(){
			global $sys_language;
			
			$select_fields = array_keys($this->items);
			$items_count = sizeof($this->form->get(reset($select_fields)));
			$value = array();
			
			for($i = 0; $i < $items_count; $i++){
				$line = array();
				
				foreach($select_fields as $select_field)
					$line[] = $this->form->get($select_field, '', $i);
				
				$value[] = implode(',', $line);
			}
			
			$count_array = array_count_values($value);

			foreach($count_array as $index => $count){
				if(!empty($index) && ($count > 1))
					return self::invalid(sprintf($sys_language->get('class_form', 'validation_repeat'), '<strong rel="'.$this->id.'">'.$this->label.'</strong>'));
			}
			
			return self::valid();
		}
	}
?>