<?php
	namespace Util;
	
	/**
	 * Classe com métodos diversos.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 21/02/2014
	*/
	
	abstract class Tools{
		/**
		 * Carrega o conteúdo de uma página include.
		 * 
		 * @param string $file Caminho completo do arquivo PHP.
		 * @param array $get_params Vetor com parâmetros GET a serem passados para a página, onde a chave é o nome do parâmetro e o valor é o valor do parâmetro.
		 * @param array $post_params Vetor com parâmetros POST a serem passados para a página, onde a chave é o nome do parâmetro e o valor é o valor do parâmetro.
		 * @return string|boolean Conteúdo da página em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function get_include_content($file, $get_params = array(), $post_params = array()){
			if(\Storage\File::exists($file)){
				global $sys_control;
				global $sys_user;
				global $sys_config;
				global $sys_assets;
				global $sys_facebook_sdk;
				global $sys_language;
				global $db;
				
				if(sizeof($get_params)){
					foreach($get_params as $key => $value)
						\HTTP\Request::set('get', $key, $value);
				}
				
				if(sizeof($post_params)){
					foreach($post_params as $key => $value)
						\HTTP\Request::set('post', $key, $value);
				}
				
				ob_start();
				include ROOT.'/'.ltrim($file, '/');
				$content = ob_get_contents();
				ob_end_clean();
				
				return $content;
			}
			
			return false;
		}
		
		/**
		 * Mostra mensagem de que o site foi encontrado por mecanismos de busca e destaca os termos da pesquisa.
		 */
		public static function search_engine_message(){
			$referer = $_SERVER['HTTP_REFERER'];
			$referer_array = explode('?', $referer);
			$referer_url = $referer_array[0];
			
			$valid_engine = false;
			
			if(($referer_url == 'http://www.google.com.br/search') || ($referer_url == 'http://www.google.com/search')){
				$engine_icon = 'google.png';
				$engine_name = 'Google';
				$search_query = \URL\URL::get_param($referer, 'q');
				$valid_engine = true;
			}
			elseif($referer_array[0] == 'http://www.bing.com/search'){
				$engine_icon = 'bing.png';
				$engine_name = 'Bing';
				$search_query = \URL\URL::get_param($referer, 'q');
				$valid_engine = true;
			}
			
			if($valid_engine){
				if(!$search_query)
					$search_query = 'default';
				
				echo '
					<div id="search-engine-box">
						<div class="engine-message" style="background-image:url(\'/site/media/images/search-engines/'.$engine_icon.'\')">
							<h2>Você chegou aqui procurando através do <strong>'.$engine_name.'</strong>!</h2>
							<p>Os termos de sua pesquisa estão destacados na cor <span class="highlight-clone">amarela</span> em nosso site para facilitá-lo a encontrar o que procura.</p>
							
							<div class="options">
								<a href="#" id="search-engine-highlight" class="highlighted">Desativar destaques</a>
								/
								<a href="#" id="search-engine-close">Fechar</a>
							</div>
						</div>
						
						<div class="clear"></div>
					</div>
					
					<script>
						function search_engine_highlight(){
							var words = "'.$search_query.'";
							$.each(words.split("+"), function(idx, val){ $("body").highlight(val); });
						}
						
						$(document).ready(function(){
							search_engine_highlight();
						});
						
						$("#search-engine-highlight").click(function(){
							if($(this).hasClass("highlighted")){
								$("body").removeHighlight();
								$(this).html("Ativar destaques");
							}
							else{
								search_engine_highlight();
								$(this).html("Desativar destaques");
							}
							
							$(this).toggleClass("highlighted");
							return false;
						});
						
						$("#search-engine-close").click(function(){
							$("#search-engine-box").remove();
							return false;
						});
					</script>
				';
			}
		}
		
		/**
		 * Carrega miniatura de vídeo do Vimeo.
		 * 
		 * @param string $video_id ID do vídeo.
		 * @param string $size Tamanho da imagem, que pode ser 'small', 'medium' ou 'large'.
		 */
		public static function vimeo_thumbnail($video_id, $size = 'small'){
			if(!in_array($size, array('small', 'medium', 'large')))
				$size = 'small';
			
			$url = 'http://vimeo.com/api/v2/video/'.$video_id.'.php';
			$contents = @file_get_contents($url);
			$vimeo_array = @unserialize(trim($contents));
			
			return $vimeo_array[0]['thumbnail_'.$size];
		}
		
		/**
		 * Detecta se é acesso do GoogleBot.
		 * 
		 * @return boolean TRUE caso seja GoogleBot ou FALSE caso não seja GoogleBot.
		 */
		public static function is_googlebot(){
			return (boolean)strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'googlebot');
		}
	}
?>