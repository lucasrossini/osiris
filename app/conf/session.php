<?php
	//Sessão de controle
	$session_name = KEY.'_session_control';
	
	//Se a sessão expirou, destrói a sessão
	if(\HTTP\Session::exists($session_name) && ((time() - \HTTP\Session::get($session_name)) > SESSION_EXPIRE))
		\HTTP\Session::destroy();
	
	//Atualiza a sessão
	\HTTP\Session::create($session_name, time());
?>