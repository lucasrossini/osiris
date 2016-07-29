<?php
	namespace XML;
	
	/**
	 * Classe que manipula dados XML.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 27/02/2014
	*/
	
	class XML{
		protected $xml;
		
		/**
		 * Cria um objeto XML.
		 * 
		 * @param array $records Vetor com os dados a serem transformados em XML, onde a chave define cada tag e o valor define o conteúdo de cada tag.
		 * @param array $group_tags Vetor com os índices 'group', que indica o nome da tag que agrupa todos os items do XML; e 'item', que indica o nome da tag que agrupa os valores de um item caso ele não possua uma chave definida.
		 */
		public function __construct($records = array(), $group_tags = array('group' => 'items', 'item' => 'item')){
			$this->xml = '<?xml version="1.0" encoding="UTF-8"?>';
			
			if($group_tags['group'])
				$this->xml .= '<'.$group_tags['group'].'>';

			foreach($records as $key => $record){
				if(is_int($key) || empty($key))
					$key = $group_tags['item'];
				
				$record_xml = self::data_xml($record);
				$this->xml .= $key ? '<'.$key.'>'.$record_xml.'</'.$key.'>' : $record_xml;
			}
			
			if($group_tags['group'])
				$this->xml .= '</'.$group_tags['group'].'>';
		}
		
		/**
		 * Retorna a string XML.
		 * 
		 * @return string Conteúdo XML.
		 */
		public function get_xml(){
			return $this->xml;
		}
		
		/**
		 * Transforma um dado em tags XML.
		 * 
		 * @param string|array $data Dado a ser transformado.
		 * @param string $default_key Tag padrão a ser utilizada caso o dado a ser transformado seja um vetor e a chave do item seja um valor numérico.
		 * @return string Conteúdo XML do dado.
		 */
		protected static function data_xml($data, $default_key = 'item'){
			$xml = '';
			
			if(is_array($data)){
				foreach($data as $key => $value){
					if(is_int($key))
						$key = $default_key;
					
					$xml .= '<'.$key.'>'.self::data_xml($value).'</'.$key.'>';
				}
			}
			else{
				$xml .= \Form\Validator::has_html($data) ? addslashes(htmlentities($data)) : addslashes($data);
			}
			
			return $xml;
		}
		
		/**
		 * Processa uma string XML.
		 * 
		 * @param string $xml Conteúdo XML.
		 * @return array Vetor com os dados do XML.
		 */
		public static function parse($xml){
			$object = new \SimpleXMLElement($xml);
			return \Util\ArrayUtil::obj2array($object);
		}
		
		/**
		 * Salva o conteúdo XML em um arquivo.
		 * 
		 * @param string $path Pasta onde o arquivo deve ser gravado.
		 * @param string $name Nome do arquivo a ser criado.
		 * @return boolean TRUE em caso de sucesso ou FALSE em caso de falha.
		 */
		public function save($path, $name){
			return \Storage\File::create($path, $name, $this->xml);
		}
		
		/**
		 * Exibe o conteúdo XML.
		 */
		public function output(){
			header('Content-type: application/xml');
			echo $this->xml;
			exit;
		}
	}
?>