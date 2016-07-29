<?php
	//Tratamento para SQL injection
	array_walk($_GET, '\Security\Sanitizer::sanitize', array('replace_var' => true));
?>