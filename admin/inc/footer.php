<footer id="footer" class="clearfix">
	<div class="credits left clearfix">
		<?php
			//Exibe a logo do site
			if(defined('LOGO')){
				$logo_obj = new \Media\Image(LOGO);
				$new_dimensions = $logo_obj->get_resize_dimensions(150, 100);
				
				echo '<a href="'.BASE.'" title="'.TITLE.'" class="logo"><img src="'.LOGO.'" width="'.$new_dimensions['width'].'" height="'.$new_dimensions['height'].'" alt="'.TITLE.'" /></a>';
			}
			
			echo '
				'.TITLE.' &mdash; '.$sys_language->get('admin_footer', 'admin_system').'<br />
				&copy; '.$sys_language->get('admin_footer', 'copyright').', '.date('Y').'
			';
		?>
	</div>
	
	<div class="development right">
		<a href="http://www.grupoemidia.com" title="<?php $sys_language->get('admin_footer', 'developed_by', true) ?> Grupo Emedia" target="_blank"><img src="admin/images/logos/grupo-emedia.png" alt="Grupo Emedia" width="120" height="41" /></a>
	</div>
</footer>