<?php
	//Efetua login
	$remember_data = \HTTP\Request::post('remember') ? true : false;

	if($sys_user->login(array('login' => array('field' => HTTP\Request::post('fields'), 'value' => \HTTP\Request::post('login')), 'password' => array('field' => 'password', 'value' => \HTTP\Request::post('password'))), '', false, $remember_data)){
		$result = array(
			'success' => true,
			'error' => ''
		);
	}
	else{
		$result = array(
			'success' => false,
			'error' => $sys_user->get_error()
		);
	}
?>