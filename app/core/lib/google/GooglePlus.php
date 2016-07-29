<?php
	namespace Google;
	
	/**
	 * Classe para integração com o Google Plus.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 13/03/2014
	*/
	
	abstract class GooglePlus{
		/**
		 * Retorna as tags do Google Plus para a página atual.
		 * 
		 * @return string HTML das tags.
		 */
		public static function get_meta_tags(){
			global $sys_control;
			
			$tags = '';
			$has_tags = false;
			
			//Tags específicas da página atual
			$current_page = $sys_control->get_url();
			$class = $sys_control->get_page_attr($current_page, 'class_name');
			$record_id = $sys_control->get_page_attr($current_page, 'record_id');
			
			if(!empty($class) && !empty($record_id)){
				$reflection_class = new \ReflectionClass($class);
				
				if($reflection_class->isSubclassOf('\Database\DatabaseObject') && $reflection_class->hasProperty('gplus_data')){
					$tags = $class::get_gplus_tags($record_id);
					$has_tags = true;
				}
			}
			
			//Tags padrão caso a página não possua tags específicas
			if(!$has_tags){
				$tags = '
					<meta itemprop="name" content="'.$sys_control->get_title().'" />
					<meta itemprop="image" content="'.BASE.'/site/media/images/facebook/logo.png" />
				';
				
				$description = '';
				
				if($sys_control->get_page_attr($sys_control->get_url(), 'subtitle'))
					$description = $sys_control->get_page_attr($sys_control->get_url(), 'subtitle');
				elseif(defined('DESCRIPTION'))
					$description = DESCRIPTION;
				
				if($description)
					$tags .= '<meta itemprop="description" content="'.$description.'" />';
			}
			
			return $tags;
		}
		
		/**
		 * Exibe o botão de "+1" para a página atual.
		 * 
		 * @param string $url Endereço da página a ser marcada com +1.
		 * @param string $size Tamanho do botão a ser exibido, que pode ser 'small', 'medium', 'standard' ou 'tall'.
		 * @param string $annotation Tipo de informação a ser exibida próxima ao botão, que pode ser 'none', 'bubble' ou 'inline'.
		 * @return string HTML da tag.
		 */
		public static function plusone_button($url = URL_WITHOUT_GETS, $size = 'medium', $annotation = 'bubble'){
			if(!in_array($size, array('small', 'medium', 'standard', 'tall')))
				$size = 'medium';
			
			if(!in_array($annotation, array('none', 'bubble', 'inline')))
				$size = 'bubble';
			
			return '<g:plusone href="'.$url.'" size="'.$size.'" annotation="'.$annotation.'"></g:plusone>';
		}
		
		/**
		 * Carrega o script do Google Plus.
		 * 
		 * @return string Script.
		 */
		public static function get_script(){
			return '
				<script>
					window.___gcfg = {lang: "pt-BR"};
					
					(function() {
					var po = document.createElement("script"); po.type = "text/javascript"; po.async = true;
					po.src = "https://apis.google.com/js/plusone.js";
					var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(po, s);
					})();
				</script>
			';
		}
	}
?>