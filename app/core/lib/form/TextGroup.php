<?php
	namespace Form;
	
	/**
	 * Classe que representa um grupo de campos de texto.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 18/02/2014
	*/
	
	class TextGroup extends Field{
		protected $item_label;
		protected $items_count;
		protected $max_items;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param array $value Valores dos itens.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param string $item_label Rótulo de cada elemento INPUT do grupo.
		 * @param int $items_count Quantidade de campos a serem exibidos inicialmente.
		 * @param int $max_items Quantidade máxima de campos (0 para ilimitado).
		 */
		public function __construct($name, $label = '', $value = array(), $attributes = array(), $item_label = '', $items_count = 1, $max_items = 0){
			parent::__construct($name, $label, $value, $attributes);
			
			$this->item_label = $item_label;
			$this->items_count = (int)$items_count;
			$this->max_items = (int)$max_items;
			
			//Remove os itens em branco
			$this->strip_empty_items();
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
							<fieldset class="fieldgroup textgroup" id="'.$this->id.'-textgroup">
								<legend class="label-title">'.$this->label.$this->label_complement.'</legend>
					';
					
					if($this->max_items)
						$this->html .= '<span class="info">* '.sprintf($sys_language->get('class_form', 'max_items'), $this->max_items).'</span>';
					
					for($i = 1; $i <= $this->items_count; $i++){
						$this->html .= '
							<label class="inline-labels">
								<span class="label-title">'.$this->item_label.' <span class="item-counter">'.$i.'</span></span>
								<input type="text" name="'.$this->name.'[]" value="'.$this->value[$i - 1].'" id="'.$this->id.'-item-'.$i.'" data-id="'.$this->id.'" />
								
								<a href="#" class="remove-item" title="'.$sys_language->get('class_form', 'remove_item').'"><img src="app/assets/images/delete.png" alt="'.$sys_language->get('class_form', 'remove_item').'" /></a>
							</label>
						';
					}
					
					$this->html .= '
							</fieldset>
							
							<a href="#" class="icon add">'.$sys_language->get('class_form', 'add_item').'</a>
							'.$this->get_tip().'
						</div>
					';
					
					//Script
					$this->script = '
						//Objeto textgroup
						var '.$this->id.'_textgroup = {
							container: $("#'.$this->id.'-textgroup"),
							items_count: '.$this->items_count.',
							max_items: '.$this->max_items.',
							
							//Adiciona um item
							add_item: function(){
								if((this.items_count < this.max_items) || (this.max_items == 0)){
									this.items_count++;
									
									var item_clone = this.container.find(".inline-labels:first").clone().find("input[type=\'text\']").val("").end().hide();
									this.container.append(item_clone).find(".inline-labels:last").fadeIn(200);

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
									this.container.find(".inline-labels:eq(" + (i - 1) + ") .item-counter").text(i).parents(".inline-labels").find("input[type=\'text\']").attr("id", function(index, attr){
										return $(this).data("id") + "-item-" + i;
									});
								}
							}
						};
						
						$("#'.$this->id.'-textgroup .remove-item").live("click", function(){
							'.$this->id.'_textgroup.remove_item($(this).parent());
							return false;
						});
						
						$("#label-'.$this->id.' .add").click(function(){
							'.$this->id.'_textgroup.add_item();
							return false;
						});
					';
					
					break;

				case 'view':
					$this->html = $this->view(\Util\ArrayUtil::listify($this->value));
					break;
			}
		}
		
		/**
		 * Remove os itens em branco.
		 */
		private function strip_empty_items(){
			if(is_array($this->value) && sizeof($this->value)){
				$this->value = \Util\ArrayUtil::remove('', $this->value);
				$this->items_count = sizeof($this->value);
				
				if(!$this->items_count)
					$this->items_count = 1;
			}
		}
		
		/**
		 * @see Field::set_value()
		 */
		public function set_value($value, $index = null){
			$this->value = $value;
			
			//Remove os itens em branco
			$this->strip_empty_items();
		}
		
		/*-- Validação --*/
		
		/**
		 * @see FieldValidator::is_empty()
		 */
		public function is_empty(){
			global $sys_language;
			
			if(!sizeof($this->value))
				return self::invalid(sprintf($sys_language->get('class_form', 'validation_empty_multi_fill'), '<strong rel="'.$this->id.'">'.$this->label.'</strong>'));
			
			return self::valid();
		}
	}
?>