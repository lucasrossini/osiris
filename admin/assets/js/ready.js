//Muda URL ao alterar uma opção de um select
function change_select(param, url){
	url += (url.indexOf('?') > 0) ? '&' + param : '?' + param;
	window.location.href = url;
}

$(document).ready(function(){
	//Aplica placeholder nos campos caso o navegador não suporte
	$('input[placeholder], textarea[placeholder]').placeholder();
	
	//Adiciona diálogo de confirmação para exclusão
	$('a.delete:not(.disabled)').live('click', function(){
		return confirm(Language.get('common', 'delete_confirm'));
	});
	
	//Tipsy
	$('.tip').tipsy({gravity: 's', offset: 5});
	
	//Seletor de idioma
	$('.language-selector > .current').click(function(e){
		$(this).parent().find('ul').addClass('open');
		e.stopPropagation();
	});
	
	$(document).click(function(){
		$('.language-selector ul').removeClass('open');
	});
	
	$('.language-selector ul > li').click(function(){
		$(this).parent().find('li').removeClass('current');
		$(this).addClass('current');
		$(this).parent().parent().children('.current').css('backgroundImage', 'url("app/lang/flags/' + $(this).data('lang') + '.png")').attr('title', $(this).text());
	});
});