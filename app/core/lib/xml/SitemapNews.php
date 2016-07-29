<?php
	namespace XML;
	
	/**
	 * Classe para geração de XML de sitemaps de notícias para o Google News.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 11/03/2014
	*/
	
	class SitemapNews extends XML{
		private $url;
		private $items;
		
		/**
		 * Instancia um objeto de sitemap de notícias.
		 *
		 * @param string $url Endereço do site.
		 * @param array $items Vetor multidimensional com os itens do sitemap, onde o parâmetro 'url' indica a URL da notícia a partir da base, o parâmetro 'title' indica o título da página que contém a notícia, o parâmetro 'genres' contém um vetor com a lista de gêneros da notícia (que pode ser 'PressRelease', 'Satire', 'Blog', 'OpEd' e 'UserGenerated'), o parâmetro 'date' indica a data de publicação da notícia no formato '0000-00-00', o parâmetro 'time' indica a hora de publicação da notícia no formato '00:00:00', o parâmetro 'keywords' contém um vetor com palavras-chave da notícia e o parâmetro 'geolocation' indica o local de publicação da notícia no formato 'Cidade, Estado, País'.
		 */
		public function __construct($url, $items = array()){
			$this->url = rtrim($url, '/');
			$this->items = $items;
			
			$this->xml = '<?xml version="1.0" encoding="UTF-8"?>
				<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
					 xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
			';
		
			if(sizeof($items)){
				foreach($items as $item){
					$this->xml .= '
						<url>
							<loc>'.$this->url.$item['url'].'</loc>
							<news:news>
								<news:publication>
									<news:name>'.TITLE.'</news:name>
									<news:language>pt</news:language>
								</news:publication>
								<news:genres>'.implode(', ', $item['genres']).'</news:genres>
								<news:publication_date>'.$item['date'].'T'.$item['time'].'-03:00</news:publication_date>
								<news:title>'.$item['title'].'</news:title>
								<news:geo_locations>'.$item['geolocation'].'</news:geo_locations>
								<news:keywords>'.implode(', ', $item['keywords']).'</news:keywords>
							</news:news>
						</url>
					';
				}
			}
		
			$this->xml .= '</urlset>';
		}
	}
?>