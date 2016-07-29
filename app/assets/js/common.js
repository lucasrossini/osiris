//Retira link das âncoras desabilitadas
$('a.disabled:not(.show-link)').removeAttr('href title');

//Muda a altura de um elemento TEXTAREA
$('textarea.expandable').focus(function(){
	if(!$(this).val())
		$(this).addClass('expanded');
});

$('textarea.expandable').blur(function(){
	if(!$(this).val())
		$(this).removeClass('expanded');
});