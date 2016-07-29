/**
 * Classe com métodos para manipulação de imagens.
 * 
 * @package Osiris
 * @author Lucas Rossini <lucasrferreira@gmail.com>
 * @version 04/04/2014
 */

function Image(){}

/**
 * Aplica caixa de legenda às imagens dos textos provenientes do editor.
 * 
 * @param container Elemento jQuery da caixa que contém as imagens.
 * @param max_width Tamanho máximo das imagens.
 */
Image.apply_subtitles = function(container, max_width){
	container.find('img').each(function(){
		$(this).hide();
		
		var self = $(this);
		var src = $(this).attr('src');
		var figure_class = $(this).css('float');
		var default_width = parseInt($(this).css('width'));
		var default_height = parseInt($(this).css('height'));
		var new_width, new_height;
		
		//Calcula o tamanho
		if(!default_width || (default_width > max_width)){
			if(figure_class == 'none'){
				new_width = parseInt(container.width());
				figure_class = 'center';
			}
			else{
				new_width = max_width;
			}
			
			new_height = parseInt((default_height * new_width) / default_width);
		}
		else{
			new_width = default_width;
			new_height = default_height;
		}
		
		//Aplica a legenda
		$.getJSON('app/core/util/ajax/handler', {page: 'thumb', image: self.attr('src'), width: new_width, height: new_height}, function(response){
			var subtitle = self.attr('alt');
			
			self.attr({
				'title': subtitle,
				'src': response.url
			}).css({
				'float': 'none',
				'width': new_width + 'px',
				'height': new_height + 'px'
			}).wrap('<figure class="' + figure_class + '" style="width: ' + new_width + 'px"></figure>').fadeIn(500);
			
			if(subtitle)
				self.parent().append('<figcaption>' + subtitle + '</figcaption>');
		});
		
		//Abre a imagem em seu tamanho original pelo Fancybox
		self.bind('click', function(){
			$.fancybox({
				type: 'image',
				href: src,
				title: self.attr('alt')
			});
		});
	});
};