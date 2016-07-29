/**
 * Classe com métodos para requisições AJAX.
 * 
 * @package Osiris
 * @author Lucas Rossini <lucasrferreira@gmail.com>
 * @version 04/04/2014
 */

function Ajax(){}

/**
 * Atributos
 */
Ajax.result_message_timer = null;
Ajax.result_message_delay = 4000;

/**
 * Carrega opções de um elemento SELECT.
 * 
 * @param data Dados enviados para a página que irá carregar as opções.
 * @param target Objeto do elemento SELECT que receberá as opções carregadas.
 * @param not_auto_default Define se não deve ser inserido um elemento oculto com o valor da opção padrão.
 * @param prevent_data Define se não deve remover os dados (atributo 'data') de valor preenchido do elemento SELECT alvo.
 */
Ajax.load_select_options = function(data, target, not_auto_default, prevent_data){
	var self = this;
	self.toggle_loader(true);
	
	target.parent().find('.empty-content').text(Language.get('common', 'loading') + '...');
	
	if(!not_auto_default && !target.data('default_text'))
		target.data('default_text', target.find('option:eq(0)').text());
	
	target.attr('disabled', true).find('option').remove().end().append('<option value="">' + Language.get('common', 'loading') + '...</option>');
	
	$.getJSON('app/core/util/ajax/handler?page=options', data, function(response){
		if(response.success){
			var options = '';
			var selected = '';
			var target_value = target.data('value');
			var records = response.records;

			for(var i = 0; i < records.length; i++){
				if(records.length == 1 && ((records[i].value === '') || (records[i].value === null))){
					options += '<option value="">' + target.data('default_text') + '</option>';
				}
				else{
					selected = (records[i].id == target_value) ? 'selected' : '';
					options += '<option value="' + records[i].id + '" ' + selected + '>' + records[i].value + '</option>';
				}
			}

			target.find('option').remove().end().append(options).removeAttr('disabled');

			if(target_value != ''){
				target.trigger('change');

				if(target.parents('.view-content').length > 0){
					target.parents('.view-content:first').text(target.find('option:selected').text());
					target.remove();
				}
				else{
					target.parents('.view-content').html('<span class="empty-content">' + Language.get('class_form', 'nothing_to_show') + '</span>');
				}
				
				if(!prevent_data)
					target.removeAttr('data-value').removeData('value');
			}
		}
		else{
			self.result_message('error', response.error);
		}
		
		self.toggle_loader(false);
	});
}

/**
 * Carrega um conteúdo HTML.
 * 
 * @param data Dados enviados para a página que irá carregar o HTML.
 * @param target Objeto do elemento que receberá o conteúdo carregado.
 */
Ajax.load_html = function(data, target){
	var self = this;
	var fade_speed = 200;
	
	self.toggle_loader(true);
	
	target.fadeTo(fade_speed, 0, function(){
		$.getJSON('app/core/util/ajax/handler?page=post', data, function(response){
			if(response.success)
				target.html('<div class="ajax-data">' + response.data + '</div>');
			else
				self.result_message('error', response.error);
			
			target.fadeTo(fade_speed, 1);
			self.toggle_loader(false);
		});
	});
}

/**
 * Carrega o endereço a partir de um CEP.
 * 
 * @param zip_code CEP do endereço.
 * @param street Objeto do elemento que receberá o nome da rua.
 * @param neighborhood Objeto do elemento que receberá o nome do bairro.
 * @param state Objeto do elemento que receberá o ID do estado.
 * @param city Objeto do elemento que receberá o ID da cidade.
 */
Ajax.load_zip_address = function(zip_code, street, neighborhood, state, city){
	if(zip_code.replace('_', '').length != 9)
		return;
	
	var self = this;
	var targets = [street, neighborhood, state, city];
	
	self.toggle_loader(true);
	
	$.each(targets, function(){
		$(this).val('').attr('disabled', true);
	});
	
	$.getJSON('app/core/util/ajax/handler', {page: 'post', a: 2, zip_code: zip_code}, function(response){
		$.each(targets, function(){
			$(this).removeAttr('disabled');
		});
		
		if(response.success){
			var address = response.data;
			
			street.val(address.street);
			neighborhood.val(address.neighborhood);
			
			state.val(address.state);
			city.data('value', address.city);
			self.load_select_options({a: 1, id: address.state}, city);
		}
		else{
			street.focus();
			self.result_message('error', response.error);
		}
		
		self.toggle_loader(false);
	});
}

/**
 * Calcula o frete.
 * 
 * @param service ID do serviço de envio desejado.
 * @param origin CEP de origem.
 * @param destiny CEP de destino.
 * @param dimensions Objeto com as dimensões do pacote (vide Package::get_dimensions()).
 * @param value Valor declarado dos objetos.
 * @param callback Função de callback que recebe um objeto com o resultado do cálculo que contém os índices 'value', que indica o valor do frete; e 'delivery_days', que indica o prazo de entrega em dias.
 */
Ajax.calculate_shipping = function(service, origin, destiny, dimensions, value, callback){
	var self = this;
	self.toggle_loader(true);
	
	$.getJSON('app/core/util/ajax/handler', {page: 'post', a: 3, service: service, origin: origin, destiny: destiny, dimensions: dimensions, value: value}, function(response){
		self.toggle_loader(false);
		
		if(!response.success){
			self.result_message('error', response.error);
			response.data = null;
		}
		
		callback(response.data);
	});
}

/**
 * Efetua login.
 * 
 * @param data Objeto com os índices 'login', que indica o login do usuário; 'password', que indica a senha do usuário; 'remember', que define se deve lembrar os dados do usuário; e 'fields', que indica um vetor com os campos considerados como login.
 * @param callback Função de callback que recebe um parâmetro que indica se o login foi efetuado ou não.
 */
Ajax.login = function(data, callback){
	var self = this;
	self.toggle_loader(true);
	
	$.post('app/core/util/ajax/handler?page=login', data, function(response){
		if(!response.success)
			self.result_message('error', response.error);
		
		self.toggle_loader(false);
		callback(response.success);
	}, 'json');
}

/**
 * Carrega mais registros em uma lista.
 * (Para carregar automaticamente ao rolar a página: $(document).scrollTop() >= (button.offset().top - $(window).height()))
 * 
 * @param button Elemento do botão que ativa o carregamento.
 * @param data Dados enviados para a página que irá carregar o HTML.
 * @param target Objeto do elemento que receberá o conteúdo carregado.
 * @param count Quantidade de itens a serem carregados.
 */
Ajax.load_more = function(button, data, target, count){
	var offset = button.data('offset'),
		loading = button.data('loading'),
		active = button.data('active');
	
	if(loading || !active)
		return;
	
	loading = true;
	button.data('label', button.text()).attr('disabled', true).addClass('loading').text(Language.get('common', 'loading') + '...');
	
	data.offset = offset;
	data.count = count;
	
	$.getJSON('app/core/util/ajax/handler?page=more', data, function(response){
		var length = response.items.length;
		
		if(length){
			for(var i = 0; i < length; i++)
				target.append(response.items[i]);

			offset += count;
		}
		
		if(!response.has_more){
			button.remove();
			active = false;
		}
		
		loading = false;
		button.removeAttr('disabled').removeClass('loading').text(button.data('label')).data({offset: offset, loading: loading, active: active});
	});
}

/**
 * Exibe/esconde aviso de carregamento global.
 * 
 * @param action Define se o aviso deve ser exibido ou não.
 */
Ajax.toggle_loader = function(action){
	switch(action){
		case true:
			clearTimeout(this.result_message_timer);
			$('#ajax-result').hide();
			$('#ajax-loader').show();
			
			break;
		
		case false:
			$('#ajax-loader').fadeOut();
			break;
	}
}

/**
 * Exibe mensagem de resultado da requisição.
 * 
 * @param type Tipo de mensagem, que pode ser 'success' ou 'error'.
 * @param message Mensagem a ser exibida.
 */
Ajax.result_message = function(type, message){
	var self = this;
	clearTimeout(this.result_message_timer);
	
	$('#ajax-loader').hide();
	$('#ajax-result').removeClass('success error').addClass(type).html(message).show();
	
	this.result_message_timer = setTimeout(function(){
		$('#ajax-result').fadeOut();
	}, self.result_message_delay);
}