<?php
	namespace HTTP;
	
	/**
	 * Classe para detecção de navegador.
	 * 
	 * @package Osiris
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @version 07/03/2014
	*/
	
	abstract class Browser{
		/**
		 * Detecta o navegador utilizado e sua versão.
		 * 
		 * @return array Vetor com os índices 'user_agent', que indica o agente; 'name', que indica o nome do navegador; 'version', que indica a versão do navegador; e 'platform', que indica o sistema operacional.
		 */
		public static function get_browser(){
			$u_agent = $_SERVER['HTTP_USER_AGENT'];
			$bname = 'Unknown';
			$platform = 'Unknown';
			$version = '';

			//Plataforma
			if(preg_match('/linux/i', $u_agent))
				$platform = 'Linux';
			elseif(preg_match('/macintosh|mac os x/i', $u_agent))
				$platform = 'Mac OS X';
			elseif(preg_match('/windows|win32/i', $u_agent))
				$platform = 'Windows';

			//Navegador
			if(preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)){
				$bname = 'Internet Explorer';
				$ub = 'MSIE';
			}
			elseif(preg_match('/Firefox/i', $u_agent)){
				$bname = 'Mozilla Firefox';
				$ub = 'Firefox';
			}
			elseif(preg_match('/Chrome/i', $u_agent)){
				$bname = 'Google Chrome';
				$ub = 'Chrome';
			}
			elseif(preg_match('/Safari/i', $u_agent)){
				$bname = 'Apple Safari';
				$ub = 'Safari';
			}
			elseif(preg_match('/Opera/i', $u_agent)){
				$bname = 'Opera';
				$ub = 'Opera';
			}
			elseif(preg_match('/Netscape/i', $u_agent)){
				$bname = 'Netscape';
				$ub = 'Netscape';
			}

			//Versão
			$known = array('Version', $ub, 'other');
			$pattern = '#(?<browser>'.join('|', $known).')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
			
			preg_match_all($pattern, $u_agent, $matches);
			$i = count($matches['browser']);
			
			if($i != 1)
				$version = (strripos($u_agent, 'Version') < strripos($u_agent, $ub)) ? $matches['version'][0] : $matches['version'][1];
			else
				$version = $matches['version'][0];
			
			if($version == null || $version == '')
				$version = '?';

			return array(
				'user_agent' => $u_agent,
				'name' => $bname,
				'version' => $version,
				'platform' => $platform
			);
		}
		
		/**
		 * Exibe uma mensagem de aviso de segurança de navegador desatualizado.
		 */
		public static function show_update_message(){
			global $sys_language;
			$browser = self::get_browser();
			
			switch($browser['name']){
				case 'Internet Explorer':
					$show_warning = ((int)$browser['version'] <= 8);
					break;
				
				case 'Mozilla Firefox':
					$show_warning = ((int)$browser['version'] < 4);
					break;
				
				default:
					$show_warning = false;
			}
			
			if($show_warning){
				$admin_user = new \User\Admin();
				$site_top_padding = $admin_user->is_logged() ? 85 : 35;
				
				echo '
					<link rel="stylesheet" href="app/assets/css/browser-warning.css" />
					
					<style>
						#page.site{ padding-top: '.$site_top_padding.'px; }
					</style>
					
					<div id="browser-warning">
						<p>'.sprintf($sys_language->get('browser_warning', 'message'), '<strong>'.$browser['name'].' '.$browser['version'].'</strong>').' <a href="http://www.updateyourbrowser.net" target="_blank">['.$sys_language->get('browser_warning', 'update').']</a></p>
					</div>
				';
			}
		}
	}
?>