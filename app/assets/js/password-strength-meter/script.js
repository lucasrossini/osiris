/**
 * Classe para cálculo de força de senha.
 * 
 * @package Osiris
 * @author Firas Kassem <phiras@gmail.com>
 * @author Lucas Rossini <lucasrferreira@gmail.com>
 * @version 08/04/2014
 */

function PasswordStrength(){}

/**
 * Calcula a força da senha.
 * 
 * @param password Senha a ser verificada.
 */
PasswordStrength.check = function(password){
	var strength_levels = {
		too_short: {text: Language.get('password_strength', 'too_short'), class_name: 'short'},
		bad: {text: Language.get('password_strength', 'weak'), class_name: 'bad'},
		good: {text: Language.get('password_strength', 'good'), class_name: 'good'},
		strong: {text: Language.get('password_strength', 'strong'), class_name: 'strong'}
	};
	
	var result = {score: 0, strength: null};
	var password_length = password.length;
	
	if(password_length < 4){
		result.strength = strength_levels.too_short;
		result.score = 5;
	}
	else{
		result.score += password_length * 4;
		result.score += parseInt(this.check_repetition(1, password).length - password_length);
		result.score += parseInt(this.check_repetition(2, password).length - password_length);
		result.score += parseInt(this.check_repetition(3, password).length - password_length);
		result.score += parseInt(this.check_repetition(4, password).length - password_length);
	
		//Possui 3 números
		if(password.match(/(.*[0-9].*[0-9].*[0-9])/))
			result.score += 5;
		
		//Possui 2 caracteres especiais
		if(password.match(/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/))
			result.score += 5;
		
		//Possui letras maiúsculas e minúsculas
		if(password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/))
			result.score += 10;
		
		//Possui letras e números
		if(password.match(/([a-zA-Z])/) && password.match(/([0-9])/))
			result.score += 15;
		
		//Possui números e caracteres especiais
		if(password.match(/([!,@,#,$,%,^,&,*,?,_,~])/) && password.match(/([0-9])/))
			result.score += 15;
		
		//Possui letras e caracteres especiais
		if(password.match(/([!,@,#,$,%,^,&,*,?,_,~])/) && password.match(/([a-zA-Z])/))
			result.score += 15;
		
		//Somente números ou letras
		if(password.match(/^\w+$/) || password.match(/^\d+$/))
			result.score -= 10;
		
		//Verifica se está no intervalo [0,100]
		if(result.score < 0)
			result.score = 0;
			
		if(result.score > 100)
			result.score = 100;
		
		//Retorna o resultado
		if(result.score < 34)
			result.strength = strength_levels.bad;
		else if(result.score < 68)
			result.strength = strength_levels.good;
		else
			result.strength = strength_levels.strong;
	}
    
	return result;
}

/**
 * Verifica e remove repetição seqüencial de caracteres na senha.
 * 
 * @param length Tamanho da repetição.
 * @param password Senha a ser verificada.
 * @example check_repetition(1, 'aaaaaaabcbc') = 'abcbc'; check_repetition(2, 'aaaaaaabcbc') = 'aabc'
 */
PasswordStrength.check_repetition = function(length, password){
	var result = '';
	var repeated, i, j;
	var password_length = password.length;
	
	for(i = 0; i < password_length; i++){
		repeated = true;
		
		for(j = 0; j < length && (j + i + length) < password_length; j++)
			repeated = repeated && (password.charAt(j + i) == password.charAt(j + i + length));
		
		if(j < length)
			repeated = false;
		
		if(repeated){
			i += length - 1;
			repeated = false;
		}
		else{
			result += password.charAt(i);
		}
	}
	
	return result;
}