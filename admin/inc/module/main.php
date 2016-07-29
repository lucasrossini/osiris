<?php
	//Parâmetros
	$package = $sys_control->get_page(0);
	$module = $sys_control->get_page(1);
	
	//Inclui o módulo referente à pagina
	\System\System::include_module($package, $module, 'main');
?>

<script>
	//Botão de cancelar
	$('#cancel_button').click(function(){
		window.location = '<?php echo 'admin/'.$package.'/'.$module.'/list' ?>';
	});
</script>