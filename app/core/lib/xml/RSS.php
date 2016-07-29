<?php
	namespace XML;
	
	/**
	 * Classe para geração de RSS.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 11/03/2014
	*/
	
	class RSS extends XML{
		private $title;
		private $url;
		private $items;
		private $description;
		private $image;
		
		/**
		 * Instancia um objeto de RSS.
		 * 
		 * @param string $title Título do RSS.
		 * @param string $url Endereço da página que contém o RSS.
		 * @param array $items Vetor multidimensional com os itens do RSS, onde o parâmetro 'title' indica o título do item; o parâmetro 'description' indica a descrição do item; o parâmetro 'url' indica URL do item; o parâmetro 'date' indica a data de publicação do item (formato 00/00/0000); e o parâmetro 'time' indica a hora de publicação do item (formato 00:00:00).
		 * @param string $description Descrição do RSS.
		 * @param string $image Caminho completo do arquivo de imagem a ser exibido no RSS.
		 */
		public function __construct($title, $url, $items = array(), $description = '', $image = ''){
			$this->title = $title;
			$this->url = $url;
			$this->items = $items;
			$this->description = $description;
			$this->image = $image;
			
			$this->xml = '<?xml version="1.0" encoding="UTF-8"?>
				<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
					<channel>
						<title>'.$this->title.'</title>
						<description>'.$this->description.'</description>
						<link>'.$this->url.'</link>
						'.$this->get_xml_image($this->image, $this->title, $this->url).'
			';
		
			if(sizeof($items)){
				foreach($items as $item){
					$image_tag = $pub_date = '';
					
					//Imagem
					if($item['image'] && \Storage\File::exists($item['image'])){
						list($width, $height) = getimagesize(ROOT.$item['image']);
						$image_tag = '<media:thumbnail url="'.BASE.$item['image'].'" width="'.$width.'" height="'.$height.'" />';
					}
					
					//Data de publicação
					if($item['date'])
						$pub_date = '<pubDate>'.\DateTime\DateTime::rfc2822($item['date'], $item['time']).'</pubDate>';
					
					$this->xml .= '
						<item>
							<title>'.$item['title'].'</title>							
							<description><![CDATA['.$item['description'].']]></description>
							<link>'.$item['url'].'</link>
							<guid isPermaLink="true">'.$item['url'].'</guid>
							'.$pub_date.'
							'.$image_tag.'
						</item>
					';
				}
			}
		
			$this->xml .= '
					</channel>
				</rss>
			';
		}
		
		/**
		 * Cria a tag XML para uma imagem de um item do RSS.
		 * 
		 * @param string $image Caminho completo do arquivo de imagem.
		 * @param string $title Título da imagem.
		 * @param string $url URL de destino da imagem.
		 * @return string XML da imagem.
		 */
		private function get_xml_image($image, $title, $url){
			$xml_image = '';
			
			if(!empty($image) && \Storage\File::exists($image)){
				list($width, $height) = getimagesize(ROOT.$image);
				
				$xml_image = '
					<image>
						<url>'.BASE.$image.'</url>
						<title>'.$title.'</title>
						<link>'.$url.'</link>
						<width>'.$width.'</width>
						<height>'.$height.'</height>
						<description>'.$title.'</description>
					</image>
				';
			}
			
			return $xml_image;
		}
		
		/**
		 * Exibe o RSS.
		 */
		public function output(){
			header('Content-Type: application/rss+xml');
			echo $this->xml;
			exit;
		}
	}
?>