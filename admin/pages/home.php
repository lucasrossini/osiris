<?php
	if(ECOMMERCE)
		URL\URL::redirect('/admin/dashboard/summary/main');
	
	echo $sys_language->get('admin_home', 'intro', true);
?>