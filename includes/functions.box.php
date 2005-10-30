<?php
/**
 * Copyright (c) 2002-2006 Aur�lien Maille
 * 
 * This file is part of Wanewsletter.
 * 
 * Wanewsletter is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * Wanewsletter is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Wanewsletter; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * @version $Id$
 */

/**
 * lang_box()
 * 
 * Construction de la liste d�roulante des langues disponibles pour le script
 * 
 * @param string $default_lang    Langue actuellement utilis�e
 * 
 * @return string
 */
function lang_box($default_lang = '')
{
	$lang_ary = array();
	
	$res = @opendir(WA_PATH . 'language/');
	while( $filename = @readdir($res) ) 
	{
		if( preg_match('/^lang_([\w_-]+)\.php$/', $filename, $match) )
		{
			$lang_ary[] = $match[1];
		}
	}
	@closedir($res);
	
	if( count($lang_ary) > 1 )
	{
		$lang_box = '<select id="language" name="language">';
		
		asort($lang_ary);
		foreach( $lang_ary AS $lang_name )
		{
			$selected = ( $default_lang == $lang_name ) ? ' selected="selected"' : '';
			$lang_box .= '<option value="' . $lang_name . '"' . $selected . '> - ' . $lang_name . ' - </option>';
		}
		
		$lang_box .= '</select>';
	}
	else
	{
		list($lang_name) = $lang_ary;
		
		$lang_box = '<span class="m-texte">' . $lang_name . '<input type="hidden" id="language" name="language" value="' . $lang_name . '" />';
	}
	
	return $lang_box;
}

/**
 * format_box()
 * 
 * Construction de la liste d�roulante des formats de newsletter
 * 
 * @param string  $select_name     Nom de la liste d�roulante
 * @param integer $default_format  Format par d�faut
 * @param boolean $option_submit   True si submit lors du changement de valeur de la liste
 * @param boolean $multi_format    True si on doit affiche �galement multi-format comme valeur
 * 
 * @return string
 */
function format_box($select_name, $default_format = 0, $option_submit = false, $multi_format = false)
{
	$format_box = '<select id="' . $select_name . '" name="' . $select_name . '"';
	
	if( $option_submit )
	{
		$format_box .= '>';//' onchange="this.form.submit();">';
	}
	else
	{
		$format_box .= '>';
	}
	
	$format_box .= '<option value="1"' . (( $default_format == FORMAT_TEXTE ) ? 'selected="selected"' : '' ) . '> - texte - </option>';
	$format_box .= '<option value="2"' . (( $default_format == FORMAT_HTML ) ? 'selected="selected"' : '' ) . '> - html - </option>';
	
	if( $multi_format )
	{
		$format_box .= '<option value="3"' . (( $default_format == FORMAT_MULTIPLE ) ? 'selected="selected"' : '' ) . '> - texte &amp; html - </option>';
	}
	
	$format_box .= '</select>';
	
	return $format_box;
}

?>