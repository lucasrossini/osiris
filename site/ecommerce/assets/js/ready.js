$(document).ready(function(){
	//Carregar imagens no scroll
	$('img.lazy').lazyload({
		effect: 'fadeIn',
		placeholder: 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='
	});
});