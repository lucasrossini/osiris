<?php
	namespace Media;
	
	/**
	 * Classe para exibir um conteúdo em Flash.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 17/01/2014
	*/
	
	abstract class Flash{
		/**
		 * Exibe o objeto Flash.
		 * 
		 * @param string $name ID a ser atribuído ao elemento OBJECT.
		 * @param string $file Endereço do arquivo SWF.
		 * @param int $width Comprimento do objeto.
		 * @param int $height Altura do objeto.
		 * @param string $wmode Window mode do objeto, que pode ser 'window', 'opaque', 'transparent', 'gpu' ou 'direct'.
		 * @param string $scale Proporção do objeto, que pode ser 'showall', 'noborder', 'exactfit' ou 'noscale'.
		 * @param string $flash_vars Variáveis Flash passadas para o objeto.
		 * @param array $params Vetor contendo atributos extra a serem atribuídos ao elemento OBJECT, onde a chave é o nome do atributo e o valor é o valor do atributo.
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public static function display($name, $file, $width, $height, $wmode = 'opaque', $scale = 'exactfit', $flash_vars = '', $params = array(), $echo = true){
			if($flash_vars)
				$flash_vars_param = '<param name="flashvars" value="'.$flash_vars.'"></param>';
			
			$wmode = in_array($wmode, array('window', 'opaque', 'transparent', 'gpu', 'direct')) ? $wmode : 'window';
			$scale = in_array($scale, array('showall', 'noborder', 'exactfit', 'noscale')) ? $wmode : 'exactfit';
			
			$html = '
				<object type="application/x-shockwave-flash" id="'.$name.'" width="'.$width.'" height="'.$height.'" data="'.$file.'" '.\UI\HTML::prepare_attr($params).'>
					<param name="movie" value="'.$file.'"></param>
					<param name="allowscriptaccess" value="always"></param>
					<param name="quality" value="high"></param>
					<param name="wmode" value="'.$wmode.'"></param>
					<param name="scale" value="'.$scale.'"></param>
					'.$flash_vars_param.'
				</object>
			';
			
			if($echo)
				echo $html;
			else
				return $html;
		}
	}
?>