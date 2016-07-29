//Coloca o cursor em uma posição
$.fn.setCursorPosition = function(pos){
	return this.setSelection(pos, pos);
};

//Seleciona o texto em um intervalo
$.fn.setSelection = function(start, end){
	this.each(function(index, elem){
		if(elem.setSelectionRange){
			elem.setSelectionRange(start, end);
		}
		else if(elem.createTextRange){
			var range = elem.createTextRange();
			
			range.collapse(true);
			range.moveEnd('character', end);
			range.moveStart('character', start);
			range.select();
		}
	});
	
	return this;
};

//Posiciona o cursor no final
$.fn.focusEnd = function(){
	this.setCursorPosition(this.val().length);
	return this;
}

//Deseleciona texto do campo
$.fn.deselect = function(){
	this.selectionEnd = this.selectionStart = -1;
	return this;
}