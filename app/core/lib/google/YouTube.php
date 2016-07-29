<?php
	namespace Google;
	
	/**
	 * Classe para manipulação de vídeos do YouTube.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 31/07/2013
	*/
	
	abstract class YouTube{
		/**
		 * Monta o código de incorporação de um vídeo.
		 * 
		 * @param string $url Endereço do vídeo.
		 * @param int $width Comprimento do vídeo.
		 * @param int $height Altura do vídeo.
		 * @param boolean $autoplay Define se o vídeo deve ser reproduzido automaticamente.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public static function embed($url, $width = 620, $height = 345, $autoplay = false, $echo = false){
			$youtube_id = \URL\URL::get_param($url, 'v');
			$movie_url = 'http://www.youtube.com/v/'.$youtube_id.'?version=3&hl=pt_BR&rel=0';
			
			if($autoplay)
				$movie_url = \URL\URL::add_params($movie_url, array('autoplay' => 1));
			
			$html = '
				<object width="'.(int)$width.'" height="'.(int)$height.'">
					<param name="movie" value="'.$movie_url.'"></param>
					<param name="allowFullScreen" value="true"></param>
					<param name="allowscriptaccess" value="always"></param>
					<param name="wmode" value="opaque"></param>
					<embed src="'.$movie_url.'" wmode="opaque" type="application/x-shockwave-flash" width="'.(int)$width.'" height="'.(int)$height.'" allowscriptaccess="always" allowfullscreen="true"></embed>
				</object>
			';
			
			if($echo)
				echo $html;
			else
				return $html;
		}
		
		/**
		 * Carrega imagem de miniatura de um vídeo.
		 * 
		 * @param string $url Endereço do vídeo.
		 * @param boolean $large Define se a imagem deve ser do tamanho grande.
		 * @return string Endereço da imagem.
		 */
		public static function thumb($url, $large = false){
			$image = $large ? 'hqdefault' : 'default';
			$youtube_id = \URL\URL::get_param($url, 'v');
			
			return 'http://img.youtube.com/vi/'.$youtube_id.'/'.$image.'.jpg';
		}
		
		/**
		 * Adiciona uma caixa de upload de vídeos.
		 * 
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @param array $video_options Vetor com os atributos do vídeo a ser enviado, onde o índice 'title' indica o título do vídeo, o índice 'description' indica a descrição do vídeo, o índice 'keywords' contém um vetor indicando as palavras-chave do vídeo e o índice 'privacy' indica o nível de privacidade do vídeo (que pode ser 'public', 'unlisted' ou 'private').
		 * @param array $widget_options Vetor com as opções do widget, onde o índice 'id' indica o ID do elemento HTML onde será inserida a caixa de upload, o índice 'width' indica o comprimento da caixa de upload, o índice 'height' indica a altura da caixa de upload e o índice 'success_message' indica o texto da mensagem a ser exibida após o envio do vídeo.
		 * @param array $player_options Vetor com as opções do player do vídeo a ser enviado, onde o índice 'show' indica se o player deve ser exibido, o índice 'id' indica o ID do elemento HTML onde será inserido o player, o índice 'width' indica o comprimento do player e o índice 'height' indica a altura do player.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public static function upload_widget($echo = false, $video_options = array('title' => '', 'description' => '', 'keywords' => array(), 'privacy' => 'public'), $widget_options = array('id' => 'youtube_upload_widget', 'width' => 640, 'height' => 390, 'success_message' => 'Seu vídeo foi enviado e, no momento, está sendo processado.'), $player_options = array('show' => false, 'id' => 'youtube_uploaded_video_player', 'width' => 640, 'height' => '390')){
			if(!in_array($video_options['privacy'], array('public', 'unlisted', 'private')))
				$video_options['privacy'] = 'public';
			
			$video_keywords = '';
			if(sizeof($video_options['keywords'])){
				foreach($video_options['keywords'] as $keyword)
					$video_keywords .= '"'.$keyword.'", ';
				
				$video_keywords = rtrim($video_keywords, ', ');
			}
			
			if($player_options['show']){
				$player_container = '<div id="'.$player_options['id'].'"></div>';
				$player_script = '
					player = new YT.Player("'.$player_options['id'].'", {
						width: '.(int)$player_options['height'].',
						height: '.(int)$player_options['width'].',
						videoId: event.data.videoId,
						events: {}
					});
				';
			}
			
			$html = '
				<div id="'.$widget_options['id'].'"></div>
				'.$player_container.'
				
				<script>
					var tag = document.createElement("script");
					tag.src = "//www.youtube.com/iframe_api";
					var firstScriptTag = document.getElementsByTagName("script")[0];
					firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
					
					var widget;
					var player;
					
					function onYouTubeIframeAPIReady(){
						widget = new YT.UploadWidget("'.$widget_options['id'].'", {
							width: '.(int)$widget_options['width'].',
							height: '.(int)$widget_options['height'].',
							webcamOnly: false,
							events: {
								"onApiReady": onApiReady,
								"onUploadSuccess": onUploadSuccess,
								"onProcessingComplete": onProcessingComplete
							}
						});
					}
					
					function onApiReady(){
						widget.setVideoTitle("'.$video_options['title'].'");
						widget.setVideoDescription("'.$video_options['description'].'");
						widget.setVideoKeywords('.$video_keywords.');
						widget.setVideoPrivacy("'.$video_options['privacy'].'");
					}
					
					function onUploadSuccess(event){
						alert("'.$widget_options['success_message'].'");
					}
					
					function onProcessingComplete(event){
						'.$player_script.'
					}
				</script>
			';
			
			if($echo)
				echo $html;
			else
				return $html;
		}
	}
?>