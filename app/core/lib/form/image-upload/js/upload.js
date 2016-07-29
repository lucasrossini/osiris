/**
 * Classe para upload de imagem com recorte.
 * 
 * @package Osiris
 * @author Lucas Rossini <lucasrferreira@gmail.com>
 * @version 08/04/2014
 */

function ImageUpload(id, width, height, folder, proportional, max_width, max_height, prefix){
	this.id = id;
	this.width = width;
	this.height = height;
	this.image_width = null;
	this.image_height = null;
	this.folder = folder;
	this.file = '';
	this.proportional = proportional;
	this.max_width = max_width;
	this.max_height = max_height;
	this.prefix = prefix;
	
	this.jcrop = {
		handler: null,
		x: 0,
		y: 0,
		w: 0,
		h: 0
	};
	
	/**
	 * Realiza o upload da imagem.
	 * 
	 * @param button Elemento do botão que ativou a função.
	 */
	this.upload = function(button){
		var self = this;
		
		Ajax.toggle_loader(true);
		button.attr('disabled', true);

		$.ajaxFileUpload({
			url: 'app/core/util/ajax/handler?page=image-upload&action=upload&folder=' + self.folder,
			secureuri: false,
			fileElementId: 'file',
			dataType: 'json',
			success: function(response){
				if(response.success){
					var old_ie = ($.browser.msie && parseInt($.browser.version) <= 8);
					var image = $('<img />').attr({src: 'app/core/util/thumb?type=jpg&width=' + self.max_width + '&height=' + self.max_height + '&image=' + response.url + '&cache=0', id: 'jcrop'});
					
					self.file = response.file;
					self.image_width = response.width;
					self.image_height = response.height;
					
					if(!old_ie)
						image.css('display', 'none');
					
					$('#crop').prepend(image);
					
					image.load(function(){
						$('#upload').hide();
						$('#crop').show();
						
						if(!old_ie){
							self.jcrop(self.image_width, self.image_height);
							$('.jcrop-holder img:last').hide();
							
							setTimeout(function(){
								$('.jcrop-holder').css('backgroundImage', 'none').animate({backgroundColor: 'black'}, 400).find('img:last').fadeTo(400, 0.5, function(){
									self.set_select();
								});
							}, 1000);
						}
						else{
							setTimeout(function(){
								self.jcrop(image.width(), image.height());
								$('.jcrop-holder').css({backgroundImage: 'none', backgroundColor: 'black'});
								
								self.set_select();
							}, 1000);
						}
						
						Ajax.toggle_loader(false);
						window.top.$.fancybox.update();
					});
				}
				else{
					Ajax.result_message('error', response.error);
					button.removeAttr('disabled');
				}
			},
			error: function(){
				Ajax.result_message('error', Language.get('image_upload', 'error_message'));
				button.removeAttr('disabled');
			}
		});
		
		return false;
	};
	
	/**
	 * Aplica o plugin de recorte na imagem.
	 * 
	 * @param width Comprimento da imagem.
	 * @param height Altura da imagem.
	 */
	this.jcrop = function(width, height){
		var self = this;
		
		$('#jcrop').Jcrop({
			onChange: function(c){
				self.update_coords(c);
			},
			onSelect: function(c){
				self.update_coords(c);
			},
			aspectRatio: self.width / self.height,
			boxWidth: self.max_width,
			boxHeight: self.max_height,
			trueSize: [width, height],
			bgColor: '#EEE',
			bgOpacity: 0.5
		}, function(){
			self.jcrop.handler = this;
		});
	};
	
	/**
	 * Seleciona o recorte inicial da imagem.
	 */
	this.set_select = function(){
		this.jcrop.handler.animateTo([100, 100, 350, 350]);
	};
	
	/**
	 * Atualiza as coordenadas do recorte.
	 * 
	 * @param c Imagem jCrop.
	 */
	this.update_coords = function(c){
		this.jcrop.x = c.x;
		this.jcrop.y = c.y;
		this.jcrop.w = c.w;
		this.jcrop.h = c.h;
	};
	
	/**
	 * Faz o recorte da imagem.
	 * 
	 * @param button Elemento do botão que ativou a função.
	 */
	this.crop = function(button){
		var self = this;
		
		Ajax.toggle_loader(true);
		button.attr('disabled', true);
		
		if(self.jcrop.w > 0){
			$.ajax({
				type: 'post',
				url: 'app/core/util/ajax/handler?page=image-upload&action=crop',
				dataType: 'json',
				data: {
					x: self.jcrop.x,
					y: self.jcrop.y,
					w: self.jcrop.w,
					h: self.jcrop.h,
					file: self.file,
					proportional: self.proportional,
					width: self.width,
					height: self.height,
					folder: self.folder,
					prefix: self.prefix
				},
				success: function(response){
					if(response.success){
						var cropped = $('<img />').attr({src: response.thumb.file, width: response.thumb.width, height: response.thumb.height}).css('display', 'none');
						var previous_folder = window.top.$('#image_remove_link_' + self.id).data('folder');
						var previous_file = window.top.$('#image_remove_link_' + self.id).data('file');

						if(previous_folder && previous_file)
							$.post('app/core/util/ajax/handler?page=image-upload&action=remove', {folder: previous_folder, file: previous_file});

						window.top.$('#image_upload_target_' + self.id + ' .image-container').remove();
						window.top.$('#image_upload_target_' + self.id).prepend(cropped).find('img').wrap('<div class="image-container" style="width:' + response.thumb.width + 'px; height:' + response.thumb.height + 'px" />');

						cropped.load(function(){
							$(this).fadeIn(300, function(){
								window.top.$('#image_upload_value_' + self.id).val(response.file);
								window.top.$('#image_upload_link_' + self.id).text(Language.get('image_upload', 'change'));
								window.top.$('#image_upload_target_' + self.id + ' .image-control-links').find('.slash, #image_remove_link_' + self.id).remove().end().append(' <span class="slash">/</span> <a href="#" id="image_remove_link_' + self.id + '" data-folder="' + self.folder + '" data-file="' + response.file + '" class="icon image-remove">' + Language.get('image_upload', 'remove') + '</a>');

								window.top.$.fancybox.close();
							});
						});
					}
					else{
						Ajax.result_message('error', response.error);
						button.removeAttr('disabled');
					}
					
					Ajax.toggle_loader(false);
				}
			});
		}
		else{
			Ajax.result_message('error', Language.get('image_upload', 'crop_area_message'));
			button.removeAttr('disabled');
		}
		
		return false;
	};
	
	/**
	 * Cancela o recorte da imagem.
	 * 
	 * @param button Elemento do botão que ativou a função.
	 */
	this.cancel = function(button){
		var self = this;
		
		Ajax.toggle_loader(true);
		button.attr('disabled', true);
		
		$.post('app/core/util/ajax/handler?page=image-upload&action=remove', {folder: self.folder, file: self.file}, function(){
			Ajax.toggle_loader(false);
			window.top.$.fancybox.close();
		});
	};
}