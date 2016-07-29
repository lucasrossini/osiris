<?php
	namespace DAO;
	
	/**
	 * Classe para registro de tipo de banner.
	 * 
	 * @author Lucas Rossini <lucasrferreira@gmail.com>
	 * @date 28/04/2014
	*/
	
	class BannerType extends \Database\DatabaseObject{
		const TABLE_NAME = 'sys_banner_type';
		
		protected $name;
		protected $width;
		protected $height;
		protected $is_popup;
		protected $is_rotative;
		protected $delay;
	}
?>