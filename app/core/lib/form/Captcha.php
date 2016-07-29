<?php
	namespace Form;
	
	/**
	 * Classe que representa um campo de captcha.
	 * 
	 * @package Osiris
	 * @uses Securimage
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 15/01/2014
	*/
	
	class Captcha extends Field{
		/**
		 * @see Field::render()
		 */
		public function render(){
			global $sys_language;
			require_once CORE_PATH.'/lib/form/securimage/securimage.php';
			
			switch($this->form->get_mode()){
				case 'insert':
				case 'edit':
					//HTML
					$this->html = '
						<div rel="'.$this->id.'" id="label-'.$this->id.'" class="label">
							<span class="label-title">'.$this->label.$this->label_complement.'</span>
							
							<div class="captcha-field">
								<div class="img-container">
									<img src="app/core/lib/form/securimage/securimage_show.php" class="image" alt="Captcha" />
									<a href="#" class="refresh">'.$sys_language->get('class_form', 'refresh_image').'</a>
								</div>

								<div class="input-container">
									<p>'.$sys_language->get('class_form', 'captcha_message').'</p>
									<input type="text" name="'.$this->name.'" id="'.$this->id.'" maxlength="6" autocomplete="off" '.\UI\HTML::prepare_attr($this->attributes).' />
								</div>

								<div class="clear"></div>
							</div>
							
							'.$this->get_tip().'
						</div>
					';

					//Script
					$this->script = '
						$("#'.$this->id.'").parents(".captcha-field").find(".refresh").click(function(){
							$(this).parent().find(".image").attr("src", "app/core/lib/form/securimage/securimage_show.php?" + Math.random());
							return false;
						});
					';
					
					break;
			}
		}
	}
?>