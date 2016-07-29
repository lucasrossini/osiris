<?php
	namespace DAO;
	
	/**
	 * Classe para registro de banner de publicidade.
	 * 
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @date 28/04/2014
	*/
	
	class Banner extends \Database\DatabaseObject{
		const TABLE_NAME = 'sys_banner';
		const BASE_PATH = '/banner/click/';
		const PATH_SIZE = 3;
		
		const TABLE_VIEWS = 'sys_banner_view',
			  TABLE_CLICKS = 'sys_banner_click';
		
		//Tipos de arquivo
		const IMAGE = 1;
		const FLASH = 2;
		
		protected $name;
		protected $banner_type;
		protected $url;
		protected $click_url;
		protected $file;
		protected $file_type;
		protected $init_date;
		protected $end_date;
		protected $forced_width;
		protected $forced_height;
		protected $new_window;
		
		public static $sitemap_data = array('hidden' => true);
		
		/**
		 * @see DatabaseObject::load()
		 */
		public function load($id, $autoload = false){
			if($record = parent::load($id, $autoload)){
				$this->banner_type = new BannerType($record->type_id);
				
				$this->click_url = self::BASE_PATH.$record->id;
				$this->file = '/uploads/banners/'.$record->file;
				$this->init_date = parent::create_date_obj($record->init_date);
				$this->end_date = parent::create_date_obj($record->end_date);
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Exibe o banner.
		 * 
		 * @param boolean $show_ad_text Indica se deve ser exibida a palavra indicando a publicidade.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public function display($show_ad_text = false, $echo = true){
			global $sys_language;
			
			$html = '';
			$width = $this->get('forced_width') ? $this->get('forced_width') : $this->get('banner_type')->get('width');
			$height = $this->get('forced_height') ? $this->get('forced_height') : $this->get('banner_type')->get('height');
			$target = $this->new_window ? '_blank' : '_self';
			
			switch($this->get('file_type')){
				case self::IMAGE: //Imagem
					$img = '<img src="'.$this->get('file').'" width="'.$width.'" height="'.$height.'" style="width:'.$width.'px; height:'.$height.'px" />';
					$banner_html = $this->get('url') ? '<a href="'.$this->get('click_url').'" target="'.$target.'">'.$img.'</a>' : $img;
					
					break;
				
				case self::FLASH: //Flash
					$wmode = $this->get('banner_type')->get('is_popup') ? 'transparent' : 'opaque';
					$link_overlay = (!$this->get('banner_type')->get('is_popup') && $this->get('url')) ? '<a href="'.$this->get('click_url').'" target="'.$target.'" class="flash-banner-overlay" style="width:'.$width.'px; height:'.$height.'px"></a>' : '';
					
					$banner_html = '
						'.$link_overlay.'
						'.\Media\Flash::display(\Formatter\String::slug($this->get('name')), $this->get('file'), $width, $height, $wmode, 'exactfit', '', array(), false).'
					';
					
					break;
			}
			
			$ad_text = ($show_ad_text && !$this->get('banner_type')->get('is_popup')) ? '<span class="ad-text">'.$sys_language->get('class_banner', 'advertising').'</span>' : '';
			
			$banner_html = '
				<div class="ad-container">
					'.$ad_text.'
					<div class="ad-media">'.$banner_html.'</div>
				</div>
			';
			
			if($this->get('banner_type')->get('is_popup') && !\HTTP\Session::exists('popup_'.$this->get('id').'_shown')){
				if((int)$this->get('file_type') === self::IMAGE){
					$popup_class = 'image';
					$close_button = '<a href="#" class="close">'.$sys_language->get('class_banner', 'close').'</a>';
				}
				else{
					$popup_class = 'flash';
					$close_button = '';
				}
				
				$html .= '
					<div class="popup-ad '.$popup_class.'">
						'.$banner_html.'
						'.$close_button.'
					</div>
					
					<script>
						//Fechar pop-up
						$(".popup-ad .close").click(function(){
							$(this).parent().remove();
							return false;
						});
						
						function close_popup(){
							$(".popup-ad").remove();
						}
					</script>
				';
				
				\HTTP\Session::create('popup_'.$this->get('id').'_shown', true);
			}
			elseif(!$this->get('banner_type')->get('is_popup')){
				$html .= $banner_html;
			}
			
			if(!empty($html))
				$this->view();
			
			if($echo)
				echo $html;
			else
				return $html;
		}
		
		/**
		 * Conta um clique no banner.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function click(){
			global $db;
			return $db->query('INSERT INTO '.self::TABLE_CLICKS.' (banner_id, date, time) VALUES ('.$this->id.', CURDATE(), CURTIME())');
		}
		
		/**
		 * Conta uma visualização no banner.
		 * 
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function view(){
			global $db;
			return $db->query('INSERT INTO '.self::TABLE_VIEWS.' (banner_id, date, time) VALUES ('.$this->id.', CURDATE(), CURTIME())');
		}
		
		/**
		 * Carrega o banner atual da data de veiculação e do tipo especificado.
		 * 
		 * @param int $type_id ID do tipo de banner.
		 * @param boolean $show_ad_text Indica se deve ser exibida a palavra indicando a publicidade.
		 * @param boolean $display Define se os banners devem ser exibidos ou se o vetor com seus objetos deve ser retornado.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string|array HTML montado caso ele não seja exibido ou vetor com objetos de banner caso solicitado.
		 */
		public static function get_current_banner($type_id, $show_ad_text = false, $display = true, $echo = true){
			//Carrega os banners
			$banners_list = self::load_all('SELECT id FROM '.self::TABLE_NAME.' WHERE type_id = '.$type_id.' AND (CURDATE() BETWEEN init_date AND end_date) ORDER BY RAND()');
			
			if($banners_list['count']){
				if($display){
					$i = 0;
					
					if($banners_list['count'] > 1){
						$banner_type = new BannerType($type_id);
						
						//Banner rotativo
						if($banner_type->get('is_rotative') && $banner_type->get('delay')){
							//Carrega os recursos necessários
							global $sys_assets;
							$sys_assets->load('js', '/app/assets/js/jquery/plugins/jquery.cycle.all.pack.js', array('charset' => 'ISO-8859-1'));
							
							$html = '<div class="rotative-banners-container">';

							foreach($banners_list['results'] as $banner){
								$display_class = $i ? 'none' : 'block';
								$html .= '<div style="display:'.$display_class.'">'.$banner->display($show_ad_text, false).'</div>';

								$i++;
							}

							$html .= '
								</div>

								<script>
									//Banner rotativo
									$(document).ready(function(){
										if(typeof(rotate_banner) == "undefined"){
											$(".rotative-banners-container object").ready(function(){
												$(".rotative-banners-container").cycle({timeout:'.($banner->get('banner_type')->get('delay') * 1000).'});
												var rotate_banner = true;
											});
										}
									});
								</script>
							';
						}
						else{
							//Banner randômico
							$html = $banners_list['results'][mt_rand(0, $banners_list['count'] - 1)]->display($show_ad_text, false);
						}
						
						if($echo)
							echo $html;
						else
							return $html;
					}
					else{
						return $banners_list['results'][0]->display($show_ad_text, $echo);
					}
				}
				else{
					return $banners_list['results'];
				}
			}
			
			return false;
		}
		
		/**
		 * Monta as consultas SQL a serem realizadas antes da remoção do registro.
		 * 
		 * @param int $id ID do registro.
		 * @return array Vetor com as consultas SQL.
		 */
		public static function get_before_delete_queries($id){
			return array(
				'DELETE FROM '.self::TABLE_CLICKS.' WHERE banner_id = '.$id,
				'DELETE FROM '.self::TABLE_VIEWS.' WHERE banner_id = '.$id
			);
		}
		
		/**
		 * Verifica se a URL é um registro válido.
		 * 
		 * @param string $url URL a ser verificada.
		 * @return array|boolean Vetor com as informações da página caso seja uma URL válida ou FALSE caso seja uma URL inválida.
		 */
		public static function check_url($url){
			global $db;
			
			$url_pieces = parent::get_current_url_pieces($url);
			$id = $url_pieces[0];
			$db->query('SELECT id, name FROM '.self::TABLE_NAME.' WHERE id = "'.$id.'"');
			
			if($db->row_count()){
				$banner = $db->result(0);
				return array('title' => $banner->name, 'subtitle' => '', 'file' => '/site/pages/banner.php', 'record_id' => $banner->id);
			}
			
			return false;
		}
	}
?>