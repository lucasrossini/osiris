<?php
	//Caminho da miniatura
	$result = array('url' => \Media\Image::thumb(\HTTP\Request::get('image'), \HTTP\Request::get('width'), \HTTP\Request::get('height')));
?>