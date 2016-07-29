<?php
	namespace Google;
	
	/**
	 * Classe para exibição de mapas do Google Maps.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 29/01/2014
	*/
	
	class GoogleMaps{
		private static $api_key = 'AIzaSyAlJv-UdYbo1OC90T53s8e2Ya7Ln1XO0Hs';
		
		private $id;
		private $address;
		private $width;
		private $height;
		private $zoom;
		private $marker;
		
		/**
		 * Instancia um objeto de Google Maps.
		 * 
		 * @param string $id ID do mapa.
		 * @param string $address Endereço desejado.
		 * @param int $width Comprimento do mapa.
		 * @param int $height Altura do mapa.
		 * @param int $zoom Nível de zoom do mapa.
		 * @param array $marker Vetor de opções do marcador do endereço, com os índices 'title', 'icon' e 'content'.
		 */
		public function __construct($id, $address, $width, $height, $zoom = 16, $marker = array()){
			$this->id = $id;
			$this->address = $address;
			$this->width = (int)$width;
			$this->height = (int)$height;
			$this->zoom = (int)$zoom;
			$this->marker = $marker;
		}
		
		/**
		 * Desenha o mapa.
		 * 
		 * @param boolean $echo Indica se o HTML montado deve ser exibido na chamada do método.
		 * @return string HTML montado caso ele não seja exibido.
		 */
		public function draw($echo = true){
			//Marcador
			$marker_script = '';
			
			if(sizeof($this->marker)){
				$marker_script = '
					//Marcador
					var marker = new google.maps.Marker({
						icon: "'.$this->marker['icon'].'",
						map: map,
						position: coords,
						title: "'.$this->marker['title'].'"
					});
					
					var info_window = new google.maps.InfoWindow();
					
					google.maps.event.addListener(marker, "click", (function(marker, i){
						return function(){
							info_window.setContent("'.addslashes($this->marker['content']).'");
							info_window.open(map, marker);
						}
					})(marker));
				';
			}
			
			//Monta o HTML
			$html = '
				<div id="'.$this->id.'" class="google-map" style="width: '.$this->width.'px; height: '.$this->height.'px"></div>
				
				<script>
					var geocoder = new google.maps.Geocoder();
					
					geocoder.geocode({address: "'.$this->address.'"}, function(results, status){
						if(status == google.maps.GeocoderStatus.OK){
							//Calcula latitude e longitude do endereço
							var coords = results[0].geometry.location;
							var latlng = new google.maps.LatLng(coords.d, coords.e);
							
							//Exibe o mapa
							var map = new google.maps.Map(document.getElementById("'.$this->id.'"), {
								zoom: '.$this->zoom.',
								center: latlng
							});
							
							'.$marker_script.'
						}
					});
				</script>
			';
			
			if($echo)
				echo $html;
			else
				return $html;
		}
		
		/**
		 * Monta a URL da imagem do mapa para o endereço.
		 * 
		 * @return string Endereço da imagem gerada.
		 */
		public function get_image_url(){
			return 'http://maps.google.com/maps/api/staticmap?center='.utf8_encode($this->address).'&zoom='.$this->zoom.'&size='.$this->width.'x'.$this->height.'&maptype=roadmap&sensor=false';
		}
		
		/**
		 * Monta a URL do iframe do mapa para o endereço.
		 * 
		 * @return string Endereço do iframe gerado.
		 */
		public function get_iframe_url(){
			return 'https://maps.google.com.br/maps?f=q&source=s_q&hl=pt-BR&geocode=&q='.utf8_encode($this->address).'&aq=t&t=m&ie=UTF8&hq=&hnear='.utf8_encode($this->address).'&z='.$this->zoom.'&iwloc=A&output=embed';
		}
		
		/**
		 * Carrega o script da API do Google Maps.
		 * 
		 * @return string Elemento script.
		 */
		public static function get_script(){
			return '<script src="http://maps.googleapis.com/maps/api/js?key='.self::$api_key.'&sensor=false"></script>';
		}
	}
?>