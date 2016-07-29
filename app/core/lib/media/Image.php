<?php
	namespace Media;
	
	/**
	 * Classe para manipulação de imagens.
	 * 
	 * @package Osiris
	 * @author Simon Jarvis <info@whdesign.co.uk>
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 18/12/2013
	*/
 
	class Image{
		const MAX_QUALITY = 100;
		
		private $image;
		private $file;
		private $type;
		private $external = false;
		
		/**
		 * Instancia um objeto de imagem.
		 * 
		 * @param string $filename Caminho completo do arquivo de imagem (que também pode ser uma URL).
		 */
		public function __construct($filename = ''){
			if(!empty($filename)){
				if(\Form\Validator::is_url($filename))
					$this->external = true;
				
				$this->file = !$this->external ? '/'.ltrim(str_replace(ROOT, '', $filename), '/') : $filename;
				
				if($this->external || \Storage\File::exists($this->file))
					$this->load($this->file);
			}
		}
		
		/**
		 * Carrega a imagem.
		 * 
		 * @param string $filename Caminho completo do arquivo de imagem (que também pode ser uma URL).
		 */
		public function load($filename){
			if(\Form\Validator::is_url($filename))
				$this->external = true;
			
			if(!$this->external){
				$this->file = '/'.ltrim(str_replace(ROOT, '', $filename), '/');
				$filename = ROOT.$this->file;
				
				$image_info = getimagesize($filename);
				$this->type = $image_info[2];
				
				switch($this->type){
					case IMAGETYPE_JPEG:
						$this->image = imagecreatefromjpeg($filename);
						break;
					
					case IMAGETYPE_GIF:
						$this->image = imagecreatefromgif($filename);
						break;
					
					case IMAGETYPE_PNG:
						$this->image = imagecreatefrompng($filename);
						break;
				}
			}
			else{
				$this->file = $filename;
				$this->image = imagecreatefromstring(\Storage\File::grab($this->file));
			}
		}
		
		/**
		 * Define o conteúdo da imagem.
		 * 
		 * @param string $string Contéudo da imagem.
		 */
		public function set_content($string){
			$this->image = imagecreatefromstring($string);
		}
		
		/**
		 * Salva a imagem em disco.
		 * 
		 * @param string $filename Caminho completo contendo o local e o nome do arquivo a ser gravado.
		 * @param string $type Tipo de imagem, que pode ser 'jpg', 'gif' ou 'png'.
		 * @param int $quality Qualidade da imagem a ser gerada, que varia de 0 a 100.
		 * @param int $permissions Permissão CHMOD a ser dada para o arquivo gerado.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function save($filename, $type = 'jpg', $quality = self::MAX_QUALITY, $permissions = 0755){
			$filename = str_replace(ROOT, '', $filename);
			$filename = ROOT.$filename;
			
			switch($type){
				case 'jpg':
				case 'jpeg':
					$result = imagejpeg($this->image, $filename, $quality);
					break;
				
				case 'gif':
					$result = imagegif($this->image, $filename);
					break;
				
				case 'png':
					$result = imagepng($this->image, $filename);
					break;
			}
			
			@chmod($filename, (int)$permissions);
			return $result;
		}
		
		/**
		 * Retorna o recurso da imagem.
		 * 
		 * @return resource Recurso da imagem.
		 */
		private function get_resource(){
			return $this->image;
		}
		
		/**
		 * Carrega o conteúdo do recurso de imagem.
		 * 
		 * @param string $type Tipo de imagem, que pode ser 'jpg', 'gif' ou 'png'.
		 * @param int $quality Qualidade da imagem a ser gerada, que varia de 0 a 100.
		 * @return string Conteúdo do recurso de imagem.
		 */
		public function get_image_string($type = 'jpg', $quality = self::MAX_QUALITY){
			if($this->is_valid()){
				ob_start();
				
				switch(strtolower($type)){
					case 'jpg':
					case 'jpeg':
						imagejpeg($this->image, '', (int)$quality);
						break;
							
					case 'gif':
						imagegif($this->image);
						break;
							
					case 'png':
						imagepng($this->image);
						break;
				}
			}
			
			$string =  ob_get_contents();
			ob_end_clean();
			
			return $string;
		}
		
		/**
		 * Verifica se é uma imagem válida.
		 * 
		 * @return boolean TRUE caso seja válida ou FALSE caso não seja válida.
		 */
		public function is_valid(){
			return !is_null($this->image);
		}
		
		/**
		 * Verifica se a imagem está localizada em outro servidor.
		 * 
		 * @return boolean TRUE caso seja externa ou FALSE caso não seja externa.
		 */
		public function is_external(){
			return $this->external;
		}
		
		/**
		 * Exibe a imagem.
		 * 
		 * @param string $type Tipo de imagem, que pode ser 'jpg', 'gif' ou 'png'.
		 * @param int $quality Qualidade da imagem a ser gerada, que varia de 0 a 100.
		 */
		public function output($type = 'jpg', $quality = self::MAX_QUALITY){
			if($this->is_valid()){
				switch(strtolower($type)){
					case 'jpg':
					case 'jpeg':
						header('Content-Type: image/jpeg');
						imagejpeg($this->image, '', (int)$quality);
						
						break;
					
					case 'gif':
						header('Content-Type: image/gif');
						imagegif($this->image);
						
						break;
					
					case 'png':
						header('Content-Type: image/png');
						imagepng($this->image);
						
						break;
				}
			}
			
			exit();
		}
		
		/**
		 * Captura o comprimento da imagem.
		 * 
		 * @return int Comprimento da imagem.
		 */
		public function get_width(){
			return imagesx($this->image);
		}
		
		/**
		 * Captura a altura da imagem.
		 * 
		 * @return int Altura da imagem.
		 */
		public function get_height(){
			return imagesy($this->image);
		}
		
		/**
		 * Redimensiona a imagem proporcionalmente para uma altura fixa.
		 * 
		 * @param int $height Altura a ser redimensionada a imagem.
		 */
		public function resize_height($height){
			$ratio = $height / $this->get_height();
			$width = $this->get_width() * $ratio;
			
			$this->resize($width, $height);
		}
		
		/**
		 * Redimensiona a imagem proporcionalmente para um comprimento fixo.
		 * 
		 * @param int $width Comprimento a ser redimensionada a imagem.
		 */
		public function resize_width($width){
			$ratio = $width / $this->get_width();
			$height = $this->get_height() * $ratio;
			
			$this->resize($width, $height);
		}
		
		/**
		 * Redimensiona a imagem sem proporções.
		 * 
		 * @param int $width Comprimento a ser redimensionada a imagem.
		 * @param int $height Altura a ser redimensionada a imagem.
		 */
		public function resize($width, $height){
			if($this->is_valid() && $width && $height){
				$width = (int)$width;
				$height = (int)$height;
				
				$new_image = imagecreatetruecolor($width, $height);
				
				if($new_image){
					imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->get_width(), $this->get_height());
					$this->image = $new_image;
				}
			}
		}
		
		/**
		 * Redimensiona a imagem por uma escala de porcentagem.
		 * 
		 * @param int $scale Escala a ser redimensionada a imagem.
		 */
		public function scale($scale){
			$width = $this->get_width() * $scale / 100;
			$height = $this->getheight() * $scale / 100;
			
			$this->resize($width, $height);
		}
		
		/**
		 * Redimensiona proporcionalmente a imagem para as maiores dimensões, somente se uma das dimensões da imagem ser maior que as novas dimensões.
		 * 
		 * @param int $width Comprimento máximo a ser redimensionada a imagem.
		 * @param int $height Altura máxima a ser redimensionada a imagem.
		 * @param boolean $force Define se a imagem deve ser redimensionada para as dimensões sem considerar as proporções.
		 */
		public function smart_resize($width, $height, $force = false){
			if(!$force){
				$new_dimensions = $this->get_resize_dimensions($width, $height);
				$width = $new_dimensions['width'];
				$height = $new_dimensions['height'];
			}
			
			$this->resize($width, $height);
		}
		
		/**
		 * Captura as dimensões proporcionais para redimensionamento.
		 * 
		 * @param int $width Comprimento máximo a ser redimensionada a imagem.
		 * @param int $height Altura máxima a ser redimensionada a imagem.
		 * @return array Vetor com as dimensões proporcionais para redimensionamento, onde o índice 'width' indica o comprimento e o índice 'height' indica a altura.
		 */
		public function get_resize_dimensions($width, $height){
			$old_width = $this->get_width();
			$old_height = $this->get_height();
			
			if(!$width){
				$ratio = $height / $this->get_height();
				$width = $this->get_width() * $ratio;
			}
			elseif(!$height){
				$ratio = $width / $this->get_width();
				$height = $this->get_height() * $ratio;
			}
			
			if(($width < $old_width) || ($height < $old_height)){
				$new_width = $width;
				$new_height = ($old_height * $width) / $old_width;
				
				if($new_height > $height){
					$new_height = $height;
					$new_width = ($old_width * $height) / $old_height;
				}
				
				$width = $new_width;
				$height = $new_height;
			}
			else{
				$width = $old_width;
				$height = $old_height;
			}
			
			return array('width' => round($width), 'height' => round($height));
		}
		
		/**
		 * Aplica uma máscara na imagem, onde somente o que é puramente vermelho na máscara é exibido na imagem resultante.
		 * 
		 * @param string $mask Caminho completo do arquivo de imagem que contém a máscara.
		 */
		public function apply_mask($mask){
			$mask_obj = new \Media\Image($mask);
			$mask = $mask_obj->get_resource();
			
			$xSize = imagesx($this->image);
			$ySize = imagesy($this->image);
			
			$newPicture = imagecreatetruecolor($xSize, $ySize);
			imagesavealpha($newPicture, true);
			imagefill($newPicture, 0, 0, imagecolorallocatealpha($newPicture, 0, 0, 0, 127));
			
			$mask_width = imagesx($mask);
			$mask_height = imagesy($mask);
			
			if($xSize != $mask_width || $ySize != $mask_height){
				$tempPic = imagecreatetruecolor($xSize, $ySize);
				imagecopyresampled($tempPic, $mask, 0, 0, 0, 0, $xSize, $ySize, $mask_width, $mask_height);
				imagedestroy($mask);
				$mask = $tempPic;
			}
			
			for($x = 0; $x < $xSize; $x++){
				for($y = 0; $y < $ySize; $y++){
					$alpha = imagecolorsforindex($mask, imagecolorat($mask, $x, $y));
					$alpha = 127 - floor($alpha['red'] / 2);
					$color = imagecolorsforindex($this->image, imagecolorat($this->image, $x, $y));
					
					imagesetpixel($newPicture, $x, $y, imagecolorallocatealpha($newPicture, $color['red'], $color['green'], $color['blue'], $alpha));
				}
			}
			
			imagedestroy($this->image);
			$this->image = $newPicture;
		}
		
		/**
		 * Aplica uma marca d'água na imagem.
		 * 
		 * @param string $watermark Caminho completo do arquivo de imagem que contém a marca d'água.
		 * @param int $x Coordenada X da imagem onde deve ser aplicada a marca.
		 * @param int $y Coordenada Y da imagem onde deve ser aplicada a marca.
		 */
		public function apply_watermark($watermark, $x = 0, $y = 0){
			$watermark = str_replace(ROOT, '', $watermark);
			$watermark = ROOT.$watermark;
			
			$overlay = imagecreatefrompng($watermark);
			
			//Dimensões
			$original_width = $this->get_width();
			$original_height = $this->get_height();
			
			$overlay_width = imagesx($overlay);
			$overlay_height = imagesy($overlay);
			$out = imagecreatetruecolor($original_width, $original_height);
			
			imagecopyresampled($out, $this->image, 0, 0, 0, 0, $original_width, $original_height, $original_width, $original_height);
			imagecopyresampled($out, $overlay, $x, $y, 0, 0, $overlay_width, $overlay_height, $overlay_width, $overlay_height);
			
			//($original_width - $overlay_width), ($original_height - $overlay_height)
			
			imagedestroy($overlay);
			$this->image = $out;
		}
		
		/**
		 * Transforma a imagem em escala de cinza.
		 */
		public function grayscale(){
			imagefilter($this->image, IMG_FILTER_GRAYSCALE);
		}
		
		/**
		 * Colore a imagem.
		 * 
		 * @param int $r Vermelho.
		 * @param int $g Verde.
		 * @param int $b Azul.
		 */
		public function colorize($r, $g, $b){
			imagefilter($this->image, IMG_FILTER_COLORIZE, $r, $g, $b);
		}
		
		/**
		 * Colore a imagem utilizando o efeito 'Multiply'.
		 * 
		 * @param int $r Vermelho.
		 * @param int $g Verde.
		 * @param int $b Azul.
		 */
		public function multiply($r, $g, $b){
			$opposite = array(255 - $r, 255 - $g, 255 - $b);
			imagefilter($this->image, IMG_FILTER_COLORIZE, -$opposite[0], -$opposite[1], -$opposite[2]);
		}
		
		/**
		 * Rotaciona uma imagem em um determinado ângulo.
		 * 
		 * @param float $degrees Ângulo de rotação.
		 */
		public function rotate($degrees){
			imageantialias($this->image, true);
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
			
			$this->image = imagerotate($this->image, $degrees, imagecolorallocatealpha($this->image, 0, 0, 0, 127));
			
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
		}
		
		/**
		 * Recorta a imagem.
		 * 
		 * @param int $x Coordenada X1 da imagem.
		 * @param int $y Coordenada Y1 da imagem.
		 * @param int $w Coordenada X2 da imagem.
		 * @param int $h Coordenada Y2 da imagem.
		 * @param int $width Comprimento da imagem a ser gerada.
		 * @param int $height Altura da imagem a ser gerada.
		 */
		public function crop($x, $y, $w, $h, $width, $height){
			$dst_image = imagecreatetruecolor($width, $height);
			imagecopyresampled($dst_image, $this->image, 0, 0, (int)$x, (int)$y, (int)$width, (int)$height, (int)$w, (int)$h);
			
			$this->image = $dst_image;
		}
		
		/*-- Métodos estáticos --*/
		
		/**
		 * Retorna uma URL para a imagem redimensionada.
		 * 
		 * @param string $file Caminho completo do arquivo da imagem a ser exibida redimensionada. 
		 * @param int $width Comprimento da imagem a ser gerada (0 para ser proporcional à altura).
		 * @param int $height Altura da imagem a ser gerada (0 para ser proporcional ao comprimento).
		 * @param int $quality Qualidade da imagem a ser gerada, que varia de 0 a 100.
		 * @param string $type Tipo da imagem a ser gerada, que pode ser 'jpg', 'gif' ou 'png'.
		 * @param boolean $cache Define se a imagem deve ser cacheada ou não.
		 * @return string URL que irá exibir a imagem redimensionada.
		 */
		public static function thumb($file, $width, $height, $quality = 100, $type = 'jpg', $cache = true){
			$type = in_array(strtolower($type), array('jpg', 'png', 'gif')) ? strtolower($type) : 'jpg';
			$thumb_url = 'app/core/util/thumb?type='.$type.'&width='.(int)$width.'&height='.(int)$height.'&quality='.(int)$quality.'&image='.\Security\Crypt::exec($file);
			
			//Verifica o cache
			if($cache){
				$cache_file = \Storage\Cache::DIR.sha1($file.'-'.(int)$width.'x'.(int)$height).'.'.$type;
				return (\Storage\File::exists($cache_file) && ((\Storage\File::exists($file) && (@filemtime(ROOT.$cache_file) == @filemtime(ROOT.$file))) || !\Storage\File::exists($file))) ? ltrim($cache_file, '/') : \URL\URL::add_params($thumb_url, array('cache' => 1));
			}
			
			return $thumb_url;
		}
		
		/**
		 * Carrega uma imagem. Se ela não existir exibe uma imagem padrão.
		 * 
		 * @param string $file Caminho completo do arquivo da imagem a ser exibida.
		 * @param string $default Caminho completo do arquivo da imagem padrão a ser exibida caso a imagem desejada não exista.
		 * @param boolean $uncacheable Define se a imagem a ser exibida não deve ser cacheada.
		 * @return string|boolean Arquivo da imagem correto em caso de sucesso ou FALSE em caso de falha.
		 */
		public static function source($file, $default = 'app/assets/images/no-photo.png', $uncacheable = false){
			if(\Storage\File::exists($file))
				return $uncacheable ? $file.'?'.date('His') : $file;
			elseif(\Storage\File::exists($default))
				return $default;
			
			return false;
		}
		
		/**
		 * Captura as dimensões (comprimento e altura) de uma imagem.
		 * 
		 * @param string $file Caminho completo do arquivo da imagem a ser processada.
		 * @return array Vetor com os índices 'width', que indica o comprimento da imagem; e 'height', que indica a altura da imagem.
		 */
		public static function get_dimensions($file){
			$dimensions = array('width' => 0, 'height' => 0);
			
			if(\Storage\File::exists($file)){
				list($width, $height) = getimagesize(ROOT.$file);
				
				$dimensions['width'] = $width;
				$dimensions['height'] = $height;
			}
			
			return $dimensions;
		}
	}
?>