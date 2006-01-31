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

if( !defined('_INC_WADB_INIT') ) {

define('_INC_WADB_INIT', true);

//
// Tables du script 
//
define('ABO_LISTE_TABLE',     $prefixe . 'abo_liste');
define('ABONNES_TABLE',       $prefixe . 'abonnes');
define('ADMIN_TABLE',         $prefixe . 'admin');
define('AUTH_ADMIN_TABLE',    $prefixe . 'auth_admin');
define('BANLIST_TABLE',       $prefixe . 'ban_list');
define('CONFIG_TABLE',        $prefixe . 'config');
define('JOINED_FILES_TABLE',  $prefixe . 'joined_files');
define('FORBIDDEN_EXT_TABLE', $prefixe . 'forbidden_ext');
define('LISTE_TABLE',         $prefixe . 'liste');
define('LOG_TABLE',           $prefixe . 'log');
define('LOG_FILES_TABLE',     $prefixe . 'log_files');
define('SESSIONS_TABLE',      $prefixe . 'session');

/**
 * G�n�re une cha�ne DSN
 * 
 * @param array $infos    Informations sur l'acc�s � la base de donn�es
 * @param array $options  Options de connexion
 */
function createDSN($infos, $options = null)
{
	if( $infos['driver'] == 'mysqli' ) {
		$infos['driver'] = 'mysql';
	}
	
	$dsn = $infos['driver'] . ':';
	if( isset($infos['host']) ) {
		$dsn .= '//';
		if( isset($infos['user']) ) {
			$dsn .= rawurlencode($infos['user']);
			
			if( isset($infos['pass']) ) {
				$dsn .= ':' . rawurlencode($infos['pass']);
			}
			
			$dsn .= '@';
		}
		
		$dsn .= rawurlencode($infos['host']);
		if( isset($infos['port']) ) {
			$dsn .= ':' . intval($infos['port']);
		}
		$dsn .= '/';
	}
	
	$dsn .= $infos['dbname'];
	
	if( is_array($options) ) {
		$dsn .= '?';
		foreach( $options as $name => $value ) {
			$dsn .= rawurlencode($name) . '=' . rawurlencode($value) . '&';
		}
		
		$dsn = substr($dsn, 0, -1);// Suppression dernier esperluette
	}
	
	return $dsn;
}

/**
 * D�compose une cha�ne DSN
 * 
 * @param string $dsn
 */
function parseDSN($dsn)
{
	if( !($dsn_parts = parse_url($dsn)) ) {
		trigger_error("Invalid DSN argument", E_USER_ERROR);
		return false;
	}
	
	$infos = $options = array();
	
	foreach( $dsn_parts as $key => $value ) {
		switch( $key ) {
			case 'scheme':
				if( !in_array($value, array('mysql', 'postgres', 'sqlite')) ) {
					trigger_error("Unsupported database", E_USER_ERROR);
					return false;
				}
				else if( $value == 'mysql' && extension_loaded('mysqli') ) {
					$value = 'mysqli';
				}
				
				$infos['driver'] = $value;
				break;
			
			case 'host':
			case 'port':
			case 'user':
			case 'pass':
				$infos[$key] = rawurldecode($value);
				break;
			
			case 'path':
				$infos['dbname'] = rawurldecode($value);
				
				if( $infos['driver'] != 'sqlite' ) {
					$infos['dbname'] = ltrim($infos['dbname'], '/');
				}
				break;
			
			case 'query':
				preg_match_all('/([^=]+)=([^&]+)(?:&|$)/', $value, $matches, PREG_SET_ORDER);
				
				foreach( $matches as $data ) {
					$options[rawurldecode($data[1])] = rawurldecode($data[2]);
				}
				break;
		}
	}
	
	if( $infos['driver'] == 'sqlite' ) {
		
		if( file_exists($infos['dbname']) && is_readable($infos['dbname']) ) {
			$fp = fopen($infos['dbname'], 'rb');
			$info = fread($fp, 15);
			fclose($fp);
			
			if( strcmp($info, 'SQLite format 3') == 0 ) {
				$infos['driver'] = 'sqlite_pdo';
			}
		}
		else if( extension_loaded('pdo') && extension_loaded('pdo_sqlite') ) {
			$infos['driver'] = 'sqlite_pdo';
		}
	}
	
	return array($infos, $options);
}

/**
 * Initialise la connexion � la base de donn�es � partir d'une cha�ne DSN
 * 
 * @param string $dsn
 */
function WaDatabase($dsn)
{
	if( !($tmp = parseDSN($dsn)) ) {
		trigger_error("Invalid DSN argument", E_USER_ERROR);
		return false;
	}
	
	list($infos, $options) = $tmp;
	
	define('SQL_DRIVER', $infos['driver']);
	
	require WA_ROOTDIR . "/includes/sql/$infos[driver].php";
	
	$db = new Wadb($infos['dbname'], $options);
	
	if( strncmp(SQL_DRIVER, 'sqlite', 6) != 0 ) {
		$infos['username'] = $infos['user'];
		$infos['passwd']   = $infos['pass'];
		
		$db->connect($infos, $options);
	}
	
	return $db;
}

}
?>
