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

if( !defined('CLASS_AUTH_INC') ) {

define('CLASS_AUTH_INC', true);

//
// permissions 
//
define('AUTH_VIEW',   1);
define('AUTH_EDIT',   2);
define('AUTH_DEL',    3);
define('AUTH_SEND',   4);
define('AUTH_IMPORT', 5);
define('AUTH_EXPORT', 6);
define('AUTH_BAN',    7);
define('AUTH_ATTACH', 8);

/**
 * Class Auth
 * 
 * Gestion des permissions des utilisateurs
 */ 
class Auth {
	
	var $listdata  = array();
	var $rowset    = array();
	
	var $auth_ary  = array(
			AUTH_VIEW   => 'auth_view',
			AUTH_EDIT   => 'auth_edit',
			AUTH_DEL    => 'auth_del',
			AUTH_SEND   => 'auth_send',
			AUTH_IMPORT => 'auth_import',
			AUTH_EXPORT => 'auth_export',
			AUTH_BAN    => 'auth_ban',
			AUTH_ATTACH => 'auth_attach'
		);
	
	/**
	 * Auth::Auth()
	 * 
	 * Initialisation de la classe, et r�cup�ration des permissions de l'utilisateur courant
	 * 
	 * @return void
	 */
	function Auth()
	{
		global $admindata;
		
		$this->read_data($admindata['admin_id']);
	}
	
	/**
	 * Auth::read_data()
	 * 
	 * R�cup�ration des permissions pour l'utilisateur demand�
	 * 
	 * @param integer $admin_id    Identifiant de l'utilisateur concern�
	 * 
	 * @return void
	 */
	function read_data($admin_id)
	{
		global $db, $admindata;
		
		$sql = "SELECT li.*, aa.auth_view, aa.auth_edit, aa.auth_del, aa.auth_send,
				aa.auth_import, aa.auth_export, aa.auth_ban, aa.auth_attach
			FROM " . LISTE_TABLE . " AS li
				LEFT JOIN " . AUTH_ADMIN_TABLE . " AS aa ON aa.admin_id = $admin_id
					AND aa.liste_id = li.liste_id
			ORDER BY li.liste_name ASC";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les donn�es des listes de diffusion', ERROR);
		}
		
		$tmp_ary = array();
		while( $row = $db->fetch_array($result) )
		{
			$tmp_ary[$row['liste_id']] = $row;
		}
		
		if( $admindata['admin_id'] != $admin_id )
		{
			return $tmp_ary;
		}
		
		$this->listdata = $tmp_ary;
	}
	
	/**
	 * Auth::check_auth()
	 * 
	 * Fonction de v�rification des permissions, selon la permission concern�e et la liste concern�e
	 * Si v�rification pour une liste particuli�re, retourne un bool�en, sinon retourne un tableau d'identifiant 
	 * des listes pour lesquelles la permission est accord�e
	 * 
	 * @param integer $auth_type    Code de la permission concern�e
	 * @param integer $liste_id     Identifiant de la liste concern�e
	 * 
	 * @return array/boolean
	 */
	function check_auth($auth_type, $liste_id = 0)
	{
		global $admindata;
		
		$auth_name = $this->auth_ary[$auth_type];
		
		if( !$liste_id )
		{
			$liste_id_ary = array();
			foreach( $this->listdata AS $liste_id => $auth_list )
			{
				if( $admindata['admin_level'] == ADMIN || !empty($auth_list[$auth_name]) )
				{
					$liste_id_ary[] = $liste_id;
				}
			}
			
			return $liste_id_ary;
		}
		else
		{
			return ( $admindata['admin_level'] == ADMIN || !empty($this->listdata[$liste_id][$auth_name]) ) ? true : false;
		}
	}
	
	/**
	 * Auth::box_auth()
	 * 
	 * Construction de la liste d�roulante oui/non pour la permission concern�e et la liste concern�e
	 * 
	 * @param integer $auth_type    Code de la permission
	 * @param array   $listdata     Tableau des permissions pour la liste en cours
	 * 
	 * @return string
	 */
	function box_auth($auth_type, $listdata)
	{
		global $lang;
		
		$auth_name = $this->auth_ary[$auth_type];
		
		$selected_yes = ( !empty($listdata[$auth_name]) ) ? ' selected="selected"' : '';
		$selected_no  = ( empty($listdata[$auth_name]) ) ? ' selected="selected"' : '';
		
		$box_auth  = '<select name="' . $auth_name . '[]">';
		$box_auth .= '<option value="1"' . $selected_yes . '> ' . $lang['Yes'] . ' </option>';
		$box_auth .= '<option value="0"' . $selected_no . '> ' . $lang['No'] . ' </option>';
		$box_auth .= '</select>';
		
		return $box_auth;
	}
}

}
?>