<?php
	namespace Social;
	
	/**
	 * Classe para Flickr.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 09/08/2012
	*/
	
	class Flickr{
		private $api_key;
		
		/**
		 * Instancia objeto Flickr.
		 * 
		 * @param string $api_key Chave API gerada pela conta.
		 */
		public function __construct($api_key){
			$this->api_key = $api_key;
		}
		
		/**
		 * Monta os parâmetros necessários para uma chamada à API.
		 * 
		 * @param string $method Ação a ser realizada pela API.
		 * @param array $others Parâmetros extras.
		 * @param string $format Formato de retorno da API.
		 * @return array Vetor com os parâmetros montados.
		 */
		private function get_params($method, $others = array(), $format = 'php_serial'){
			$params = array(
				'method' => $method,
				'api_key' => $this->api_key,
				'format' => $format
			);
			
			foreach($others as $key => $value)
				$params[$key] = $value;
			
			return $params;
		}
		
		/**
		 * Cria objeto de retorno da API.
		 * 
		 * @param array $params Parâmetros passados para a chamada API.
		 * @return array|boolean Vetor com o resultado da API em caso de sucesso ou FALSE em caso de falha.
		 */
		private function create($params){
			$encoded_params = array();
			
			if(sizeof($params)){
				foreach($params as $key => $value)
					$encoded_params[] = urlencode($key).'='.urlencode($value);
		
				$url = 'http://api.flickr.com/services/rest/?'.implode('&', $encoded_params);
				
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				
				$response = curl_exec($ch);
				curl_close($ch);
				
				$object = unserialize($response);
				
				return ($object['stat'] == 'ok') ? $object : false;
			}
			
			return false;
		}
		
		/**
		 * Carrega os álbuns de um usuário.
		 * 
		 * @param string $user_id ID do usuário.
		 * @param int $limit Quantidade de álbums a serem carregados.
		 * @return array Vetor com uma lista de objetos FlickrAlbum.
		 */
		public function get_albums($user_id, $limit = 0){
			$other_params = array('user_id' => $user_id);
			
			if($limit){
				$other_params['per_page'] = (int)$limit;
				$other_params['page'] = 1;
			}
			
			$params = $this->get_params('flickr.photosets.getList', $other_params);
			$object = $this->create($params);
			
			$albums_list = array();
			
			foreach($object['photosets']['photoset'] as $album)
				$albums_list[] = new FlickrAlbum($album);
			
			return $albums_list;
		}
		
		/**
		 * Carrega os dados de um álbum.
		 * 
		 * @param int $id ID do álbum.
		 * @return FlickrAlbum Objeto que contém os dados do álbum.
		 */
		public function get_album_info($id){
			$params = $this->get_params('flickr.photosets.getInfo', array('photoset_id' => $id));
			$object = $this->create($params);
			
			$album = new FlickrAlbum($object['photoset']);
			
			return $album;
		}
		
		/**
		 * Carrega as fotos de um álbum.
		 * 
		 * @param int $id ID do álbum.
		 * @param int $limit Quantidade de fotos a serem carregadas.
		 * @return array Vetor com uma lista de objetos FlickrPhoto.
		 */
		public function get_album_photos($id, $limit = 0){
			$other_params = array('photoset_id' => $id);
			
			if($limit){
				$other_params['per_page'] = (int)$limit;
				$other_params['page'] = 1;
			}
			
			$params = $this->get_params('flickr.photosets.getPhotos', $other_params);
			$object = $this->create($params);
			
			$photos_list = array();
			
			foreach($object['photoset']['photo'] as $photo)
				$photos_list[] = new FlickrPhoto($photo);
			
			return $photos_list;
		}
		
		/**
		 * Carrega as fotos mais recentes de um usuário.
		 * 
		 * @param int $user_id ID do usuário.
		 * @param int $limit Quantidade de fotos a serem carregadas.
		 * @return array Vetor com uma lista de objetos FlickrPhoto.
		 */
		public function get_latest_photos($user_id, $limit = 0){
			$other_params = array('user_id' => $user_id);
			
			if($limit){
				$other_params['per_page'] = (int)$limit;
				$other_params['page'] = 1;
			}
			
			$params = $this->get_params('flickr.photos.search', $other_params);
			$object = $this->create($params);
			
			$photos_list = array();
			
			if(sizeof($object['photos']['photo'])){
				foreach($object['photos']['photo'] as $photo)
					$photos_list[] = new FlickrPhoto($photo);
			}
			
			return $photos_list;
		}
	}
	
	/**
	 * Classe para álbum de fotos do Flickr.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 27/07/2012
	*/
	
	class FlickrAlbum{
		private $id;
		private $title;
		private $last_update;
		private $primary_photo;
		private $primary_photo_medium;
		private $slug;
		
		/**
		 * Instancia um objeto de álbum do Flickr.
		 * 
		 * @param array $album_array Vetor do objeto retornado pela chamada à API.
		 */
		public function __construct($album_array = array()){
			if(sizeof($album_array))
				$this->load($album_array);
		}
		
		/**
		 * Carrega os dados do álbum.
		 * 
		 * @param array $album_array Vetor do objeto retornado pela chamada à API.
		 */
		public function load($album_array){
			$this->id = $album_array['id'];
			$this->title = $album_array['title']['_content'];
			$this->last_update = $album_array['date_update'];
			$this->primary_photo = 'http://farm'.$album_array['farm'].'.staticflickr.com/'.$album_array['server'].'/'.$album_array['primary'].'_'.$album_array['secret'].'_t.jpg';
			$this->primary_photo_medium = 'http://farm'.$album_array['farm'].'.staticflickr.com/'.$album_array['server'].'/'.$album_array['primary'].'_'.$album_array['secret'].'_m.jpg';
			$this->slug = \Formatter\String::slug($album_array['title']['_content']);
		}
		
		/**
		 * Retorna um atributo do álbum.
		 * 
		 * @param string $attr Nome do atributo.
		 * @return string|boolean Valor do atributo caso ele exista ou FALSE caso ele não exista.
		 */
		public function get($attr){
			$class_name = get_class($this);
			$vars = get_class_vars($class_name);
			
			if(array_key_exists($attr, $vars))
				return $this->$attr;
			
			return false;
		}
	}
	
	/**
	 * Classe para foto do Flickr.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 27/07/2012
	*/
	
	class FlickrPhoto{
		private $id;
		private $title;
		private $farm;
		private $secret;
		private $server;
		private $url;
		private $url_big;
		private $url_medium;
		
		/**
		 * Instancia um objeto de foto do Flickr.
		 *
		 * @param array $photo_array Vetor do objeto retornado pela chamada à API.
		 */
		public function __construct($photo_array){
			if(sizeof($photo_array))
				$this->load($photo_array);
		}
		
		/**
		 * Carrega os dados da foto.
		 * 
		 * @param array $photo_array Vetor do objeto retornado pela chamada à API.
		 */
		public function load($photo_array){
			$this->id = $photo_array['id'];
			$this->title = $photo_array['title'];
			$this->farm = $photo_array['farm'];
			$this->secret = $photo_array['secret'];
			$this->server = $photo_array['server'];
			$this->url = 'http://farm'.$photo_array['farm'].'.staticflickr.com/'.$photo_array['server'].'/'.$photo_array['id'].'_'.$photo_array['secret'].'_t.jpg';		
			$this->url_big = 'http://farm'.$photo_array['farm'].'.staticflickr.com/'.$photo_array['server'].'/'.$photo_array['id'].'_'.$photo_array['secret'].'_c.jpg';	
			$this->url_medium = 'http://farm'.$photo_array['farm'].'.staticflickr.com/'.$photo_array['server'].'/'.$photo_array['id'].'_'.$photo_array['secret'].'_m.jpg';	
		}
		
		/**
		 * Retorna um atributo da foto.
		 * 
		 * @param string $attr Nome do atributo.
		 * @return string|boolean Valor do atributo caso ele exista ou FALSE caso ele não exista.
		 */
		public function get($attr){
			$class_name = get_class($this);
			$vars = get_class_vars($class_name);
			
			if(array_key_exists($attr, $vars))
				return $this->$attr;
			
			return false;
		}
	}
?>