<div id="bar" class="clearfix">
	<?php
		//Palavras da seção na língua definida
		$lang = $sys_language->get('admin_header');

		if($sys_user->is_logged()){
			$personal_data_info = \System\System::load_module_info('system', 'admin', 'personal-data');
			$permission = $sys_user->get_permission('admin', 'personal-data', 'edit');

			if($permission['granted'])
				$personal_data_link = '/ <a href="admin/admin/personal-data/main">'.$personal_data_info->name.'</a>';

			echo '
				<div class="welcome left">
					<a href="admin/admin/personal-data/main" title="'.$sys_user->get('name').'" class="photo"><img src="'.\Media\Image::thumb(\Media\Image::source('/uploads/images/admins/'.$sys_user->get('photo'), 'app/assets/images/no-user-photo.png'), 45, 45).'" width="45" height="45" /></a>
					'.$lang['welcome'].', <a href="admin/admin/personal-data/main" title="'.$sys_user->get('name').'" class="user">'.\Formatter\String::firstname($sys_user->get('name')).'</a>!
				</div>
				
				<div class="options right">
					<a href="'.BASE.'">&laquo; '.$lang['go_to'].' <strong>'.TITLE.'</strong></a>
					'.$personal_data_link.'
					/ <a href="admin/logout">'.$lang['logout'].'</a>
				</div>
			';
		}
		else{
			echo $lang['welcome'].', '.$lang['visitor'].'!';
		}
	?>
</div>