<?php
	//Página a ser redirecionada após o logout
	$redirect = \HTTP\Request::get('next') ? \HTTP\Request::get('next') : '/admin/login';
	
	//Efetua o logout
	$sys_user->logout($redirect);
?>