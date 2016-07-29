/**
 * Classe para multilinguagem.
 * 
 * @package Osiris
 * @author Lucas Rossini <lucasrferreira@gmail.com>
 * @version 08/04/2014
 */

function Language(){}

/**
 * Atributos
 */
Language.entries = {};

/**
 * Carrega opções de um elemento SELECT.
 * 
 * @param section Seção onde está localizada a entrada.
 * @param entry Nome da entrada.
 * @return Valor da entrada no idioma atual.
 */
Language.get = function(section, entry){
	return Language.entries[section][entry];
}