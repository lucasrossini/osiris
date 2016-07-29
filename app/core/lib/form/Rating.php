<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo de avaliação.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 15/01/2014
	*/
	
	class Rating extends Field{
		protected $max;
		protected $show_score;
		protected $scores;
		
		/**
		 * Instancia um novo campo.
		 * 
		 * @param string $name Nome do campo.
		 * @param string $label Rótulo do campo.
		 * @param int $value Valor do campo.
		 * @param array $attributes Vetor de atributos do elemento HTML do campo, onde a chave representa o nome do atributo e o valor representa o valor do atributo.
		 * @param int $max Valor máximo possível para avaliação.
		 * @param boolean $show_score Define se os rótulos equivalentes ao valor da avaliação devem ser exibidos.
		 * @param array $scores Vetor com os rótulos equivalentes a cada valor de avaliação, onde a chave é o valor da avaliação e o valor é o texto do rótulo.
		 */
		public function __construct($name, $label = '', $value = 0, $attributes = array(), $max = 5, $show_score = true, $scores = array()){
			global $sys_language;
			parent::__construct($name, $label, $value, $attributes);
			
			$this->max = (int)$max;
			$this->show_score = $show_score;
			
			if($this->value > $this->max)
				$this->value = $this->max;
			
			if($show_score){
				if(!sizeof($scores)){
					$scores = array(
						1 => $sys_language->get('class_form', 'rating_1'),
						2 => $sys_language->get('class_form', 'rating_2'),
						3 => $sys_language->get('class_form', 'rating_3'),
						4 => $sys_language->get('class_form', 'rating_4'),
						5 => $sys_language->get('class_form', 'rating_5')
					);
				}
				
				//Divide os rótulos para cada faixa de pontos caso não sejam suficientes
				$scores_count = sizeof($scores);
				
				if($scores_count < $max){
					$interval = ceil($max / $scores_count);
					$interval_index = 1;
					$aux_scores = array();
					
					for($i = 1; $i <= $max; $i++){
						$aux_scores[$i] = $scores[$interval_index];
						
						if(!($i % $interval))
							$interval_index++;
					}
					
					$scores = $aux_scores;
				}
			}
			else{
				$scores = array();
			}
			
			$this->scores = $scores;
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
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							
							<div class="rating-container" id="'.$this->id.'-rating-container">
								<input type="hidden" name="'.$this->name.'" id="'.$this->id.'" value="'.$this->value.'" />
					';
					
					for($i = 1; $i <= $this->max; $i++){
						$lighted = ($i <= $this->value) ? 'lighted current' : '';
						$this->html .= '<span class="rate '.$lighted.'" rel="'.$i.'" title="'.$i.' '.$sys_language->get('class_form', 'of').' '.$this->max.'"></span>';
					}

					if(sizeof($this->scores))
						$this->html .= '<div class="score"><span>'.$this->scores[$this->value].'</span></div>';

					$this->html .= '
								<div class="clear"></div>
							</div>
							
							'.$this->get_tip().'
						</div>
					';

					//Script
					$array_script = '';
					
					if(sizeof($this->scores)){
						foreach($this->scores as $rating => $score)
							$array_script .= $this->id.'_labels['.$rating.'] = "'.$score.'";';
					}
					
					$this->script = '
						//Avaliação
						var '.$this->id.'_labels = new Array();
						var '.$this->id.'_current_label = "'.$this->scores[$this->value].'";
						'.$array_script.'

						$("#'.$this->id.'-rating-container .rate").hover(
							function(){
								$(this).parent().find(".rate").removeClass("lighted").addClass("hover");
								$(this).addClass("lighted").prevAll(".rate").addClass("lighted");
								$(this).parent().find(".score span").text('.$this->id.'_labels[$(this).attr("rel")]);
							},
							function(){
								$(this).parent().find(".rate").removeClass("lighted hover");
								$(this).parent().find(".score span").text('.$this->id.'_current_label);
							}
						);

						$("#'.$this->id.'-rating-container .rate").click(function(){
							$("#'.$this->id.'").val($(this).attr("rel"));
							$(this).parent().find(".rate").removeClass("current");
							$(this).addClass("current").prevAll(".rate").addClass("current");
							'.$this->id.'_current_label = '.$this->id.'_labels[$(this).attr("rel")];
						});
					';
					
					break;

				case 'view':
					$content = '<div class="rating-container readonly">';
					
					for($i = 1; $i <= $this->max; $i++){
						$lighted = ($i <= $this->value) ? 'lighted current' : '';
						$content .= '<span class="rate '.$lighted.'"></span>';
					}

					if(sizeof($this->scores))
						$content .= '<div class="score"><span>'.$this->scores[$this->value].'</span></div>';

					$content .= '
							<div class="clear"></div>
						</div>
					';
					
					$this->html = $this->view($content);
					break;
			}
		}
	}
?>