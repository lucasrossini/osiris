<?php
	namespace XML;
	
	/**
	 * Classe para geração de XML de mapa do site.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 11/03/2014
	*/
	
	class Sitemap extends XML{
		private $url;
		private $items;
		
		/**
		 * Instancia um objeto de mapa do site.
		 * 
		 * @param string $url Endereço do site.
		 * @param array $items Vetor multidimensional com os itens do mapa do site, onde o parâmetro 'page' indica a URL da página a partir da base, o parâmetro 'priority' indica a prioridade da página e o parâmetro 'changefreq' indica a frequência de mudança da página entre 0 e 1, que pode ser 'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly' ou 'never'.
		 */
		public function __construct($url, $items = array()){
			$this->url = rtrim($url, '/');
			$this->items = $items;
			
			$this->xml = '<?xml version="1.0" encoding="UTF-8"?>
				<urlset
					xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
					xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
						http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
			';
		
			if(sizeof($items)){
				$frequences = array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never');
				
				foreach($items as $item){
					if(!in_array($item['changefreq'], $frequences))
						$item['changefreq'] = 'monthly';
					
					$priority = !$item['priority'] ? '0.60' : $item['priority'];
					
					$this->xml .= '
						<url>
							<loc>'.$this->url.'/'.ltrim($item['page'], '/').'</loc>
							<priority>'.$priority.'</priority>
							<changefreq>'.$item['changefreq'].'</changefreq>
						</url>
					';
				}
			}
		
			$this->xml .= '</urlset>';
		}
	}
?>