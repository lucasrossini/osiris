<?php
	namespace Social;
	
	/**
	 * Classe para redes sociais.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 13/11/2012
	*/
	
	abstract class Social{
		/**
		 * Monta o link de compartilhamento para uma página.
		 * 
		 * @param string $title Título do compartilhamento.
		 * @param string $url URL da página a ser compartilhada.
		 * @param string $service Serviço de compartilhamento a ser utilizado.
		 * @param string $image Caminho completo do arquivo de imagem para ilustrar o compartilhamento.
		 * @param string $description Descrição do compartilhamento.
		 * @return string URL do compartilhamento.
		 */
		public static function share_link($title, $url, $service, $image = '', $description = ''){
			switch($service){
				case 'facebook':
					$share_link = 'http://www.facebook.com/share.php?u='.urlencode($url);
					break;
				
				case 'twitter':
					$share_link = 'http://twitter.com/home?status='.urlencode($title.' - '.$url);
					break;
				
				case 'orkut':
					$share_link = 'http://promote.orkut.com/preview?nt=orkut.com&tt='.urlencode($title).'&cn='.urlencode($description).'&tn='.urlencode($image).'&du='.urlencode($url);
					break;
				
				case 'email':
					$share_link = 'mailto:?body='.TITLE.'%0a'.BASE.'%0a%0d'.$title.':%0a%0d'.$url.'&subject='.$title;
					break;
			}
			
			return $share_link;
		}
		
		/**
		 * Carrega um avatar do Gravatar.
		 * 
		 * @param string $email Endereço de e-mail da conta no Gravatar.
		 * @param int $size Tamanho, em pixels, do avatar.
		 * @param string $d
		 * @param string $r
		 * @return string URL da imagem do avatar.
		 */
		public static function gravatar($email, $size = 80, $d = 'mm', $r = 'g'){
			return 'http://www.gravatar.com/avatar/'.md5(strtolower(trim($email))).'?s='.(int)$size.'&d='.$d.'&r='.$r;
		}
	}
?>