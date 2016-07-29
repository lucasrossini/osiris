<?php
	namespace Media;
	
	/**
	 * Classe para geração de galeria de fotos.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 11/04/2014
	*/
	
	class Gallery{
		const THUMB_WIDTH = 100;
		const THUMB_HEIGHT = 70;
		
		private $id;
		private $photos;
		private $dimensions;
		
		/**
		 * Instancia um objeto de galeria de fotos.
		 * 
		 * @param string $id ID da caixa que contém a galeria 
		 * @param array $photos Vetor multidimensional com as fotos da galeria, contendo os índices 'file', que indica o caminho completo do arquivo de imagem; e 'subtitle', que indica a legenda da imagem.
		 * @param array $dimensions Vetor com as dimensões máximas da imagem aberta, com os índices 'width', que indica o comprimento da imagem; e 'height', que indica a altura da imagem.
		 */
		public function __construct($id, $photos = array(), $dimensions = array('width' => 600, 'height' => 400)){
			$this->id = $id;
			$this->photos = $photos;
			$this->dimensions = $dimensions;
			
			//Verifica se os arquivos de imagem existem
			if(sizeof($this->photos)){
				foreach($this->photos as $key => $photo){
					if(!is_array($photo) || !\Storage\File::exists($photo['file']))
						unset($this->photos[$key]);
				}
			}
		}
		
		/**
		 * Exibe a galeria.
		 * 
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return boolean|string HTML montado caso ele não seja exibido ou FALSE caso a galeria não possua nenhuma foto.
		 */
		public function display($echo = true){
			global $sys_assets;
			
			$html = '';
			$per_page = $this->dimensions['width'] / self::THUMB_WIDTH;
			$photos_count = sizeof($this->photos);
			
			if($photos_count){
				//Carrega os recursos necessários
				$sys_assets->load('css', 'app/assets/gallery/gallery.css');
				$sys_assets->load('css', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.css');
				$sys_assets->load('js', 'app/assets/js/jquery/plugins/fancybox/jquery.fancybox.pack.js', array('charset' => 'ISO-8859-1'));
				$sys_assets->load('js', 'app/assets/js/jquery/plugins/jquery.bxSlider.pack.js', array('charset' => 'ISO-8859-1'));
				
				//Lista de imagens
				$i = 0;
				$list_html = '';
				
				foreach($this->photos as $photo){
					$list_html .= '
						<li style="width: '.self::THUMB_WIDTH.'px">
							<a href="'.$photo['file'].'" title="'.$photo['subtitle'].'" class="image-container '.(!$i ? 'current' : '').'" style="width: '.self::THUMB_WIDTH.'px; height: '.self::THUMB_HEIGHT.'px">
								<img src="'.Image::thumb($this->photos[$i]['file'], self::THUMB_WIDTH, self::THUMB_HEIGHT).'" alt="'.$photo['subtitle'].'" />
							</a>
						</li>
					';
					
					$i++;
				}
				
				//Controle do slider
				if($photos_count > $per_page){
					$slider_controls_html = '
						<div class="slider-controls">
							<a href="#" class="prev">&lsaquo;</a>
							<a href="#" class="next">&rsaquo;</a>
						</div>
					';
					
					$slider_script = '
						//Slider
						var '.$this->id.'_slider = $("#'.$this->id.' > .slider").bxSlider({
							displaySlideQty: '.$per_page.',
							moveSlideQty: '.round($per_page / 2).',
							controls: false
						});
						
						$("#'.$this->id.' > .slider-controls > .prev").click(function(){
							'.$this->id.'_slider.goToPreviousSlide();
							return false;
						});

						$("#'.$this->id.' > .slider-controls > .next").click(function(){
							'.$this->id.'_slider.goToNextSlide();
							return false;
						});
					';
				}

				//Monta o HTML da galeria
				$html = '
					<div id="'.$this->id.'" class="photo-gallery" style="width: '.$this->dimensions['width'].'px">
						<div class="main-image image-container" style="width: '.$this->dimensions['width'].'px; height: '.$this->dimensions['height'].'px">
							<figure>
								<a href="'.$this->photos[0]['file'].'"><img src="'.Image::thumb($this->photos[0]['file'], $this->dimensions['width'], $this->dimensions['height']).'" alt="'.$this->photos[0]['subtitle'].'" /></a>
								<figcaption>'.$this->photos[0]['subtitle'].'</figcaption>
							</figure>
						</div>
						
						<ul class="slider" style="height: '.self::THUMB_HEIGHT.'px">'.$list_html.'</ul>
						'.$slider_controls_html.'
					</div>
				';

				$html .= '
					<script>
						$(document).ready(function(){
							//Zoom nas fotos
							$("#'.$this->id.' > .main-image > figure > a").click(function(){
								$.fancybox({
									type: "image",
									href: $(this).attr("href"),
									title: $(this).find("img").attr("alt")
								});
								
								return false;
							});

							//Mudança de fotos
							$("#'.$this->id.' .slider a").live("click", function(){
								if(!$(this).hasClass("current")){
									var self = $(this);
									var subtitle = $(this).find("img").attr("alt");

									$("#'.$this->id.' .slider a").removeClass("current");
									$(this).addClass("current");
									$("#'.$this->id.' > .main-image").addClass("loading");

									$.getJSON("app/core/util/ajax/handler", {page: "thumb", image: $(this).attr("href"), width: '.$this->dimensions['width'].', height: '.$this->dimensions['height'].'}, function(response){
										$("#'.$this->id.' > .main-image > figure").find("figcaption").text(subtitle).end().find("a").attr("href", self.attr("href")).end().find("img").attr({src: response.url, alt: subtitle}).load(function(){
											$("#'.$this->id.' > .main-image").removeClass("loading");
										});
									});
								}

								return false;
							});
							
							'.$slider_script.'
						});
					</script>
				';
			}
			
			//Exibe a galeria
			if(!$echo)
				return $html;
			
			echo $html;
		}
	}
?>