<?php
/**
 * Copyright (c) 2002-2014 Aur�lien Maille
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
	else if( $infos['driver'] == 'sqlite_pdo' || $infos['driver'] == 'sqlite3' ) {
		$infos['driver'] = 'sqlite';
	}
	
	$connect = '';
	
	if( isset($infos['user']) ) {
		$connect .= rawurlencode($infos['user']);
		
		if( isset($infos['pass']) ) {
			$connect .= ':' . rawurlencode($infos['pass']);
		}
		
		$connect .= '@';
		
		if( empty($infos['host']) ) {
			$infos['host'] = 'localhost';
		}
	}
	
	if( !empty($infos['host']) ) {
		$connect .= rawurlencode($infos['host']);
		if( isset($infos['port']) ) {
			$connect .= ':' . intval($infos['port']);
		}
	}
	
	if( !empty($connect) ) {
		$dsn = sprintf('%s://%s/%s', $infos['driver'], $connect, $infos['dbname']);
	}
	else {
		$dsn = sprintf('%s:%s', $infos['driver'], $infos['dbname']);
	}
	
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
	if( !($dsn_parts = parse_url($dsn)) || !isset($dsn_parts['scheme']) ) {
		return false;
	}
	
	$infos = $options = array();
	$label = array('mysql' => 'MySQL', 'postgres' => 'PostgreSQL', 'sqlite' => 'SQLite');
	
	foreach( $dsn_parts as $key => $value ) {
		switch( $key ) {
			case 'scheme':
				if( !in_array($value, array('mysql', 'postgres', 'sqlite')) ) {
					trigger_error("Unsupported database", CRITICAL_ERROR);
					return false;
				}
				else {
					$infos['label'] = $label[$value];
					
					if( $value == 'mysql' && extension_loaded('mysqli') ) {
						$value = 'mysqli';
					}
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
				
				if( $infos['driver'] != 'sqlite' && isset($infos['host']) ) {
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
		
		if( class_exists('SQLite3') ) {
			$infos['driver'] = 'sqlite3';
		}
		else if( extension_loaded('pdo') && extension_loaded('pdo_sqlite') ) {
			$infos['driver'] = 'sqlite_pdo';
		}
		else if( !extension_loaded('sqlite') ) {
			trigger_error("No SQLite3, PDO/SQLite or SQLite extension loaded !", CRITICAL_ERROR);
		}
		
		if( is_readable($infos['dbname']) && filesize($infos['dbname']) > 0 ) {
			$fp = fopen($infos['dbname'], 'rb');
			$info = fread($fp, 15);
			fclose($fp);
			
			if( strcmp($info, 'SQLite format 3') == 0 ) {
				if( $infos['driver'] == 'sqlite' ) {
					trigger_error("No SQLite3 or PDO/SQLite extension loaded !", CRITICAL_ERROR);
				}
			}
			else if( $infos['driver'] != 'sqlite' ) {
				if( !extension_loaded('sqlite') ) {
					trigger_error("SQLite extension isn't loaded !", CRITICAL_ERROR);
				}
				else {
					$infos['driver'] = 'sqlite';
				}
			}
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
		trigger_error("Invalid DSN argument", CRITICAL_ERROR);
		return false;
	}
	
	list($infos, $options) = $tmp;
	$dbclass = 'Wadb_' . $infos['driver'];
	
	if( !class_exists($dbclass) ) {
		require WA_ROOTDIR . "/includes/sql/$infos[driver].php";
	}
	
	$infos['username'] = isset($infos['user']) ? $infos['user'] : null;
	$infos['passwd']   = isset($infos['pass']) ? $infos['pass'] : null;
	
	$db = new $dbclass($infos['dbname']);
	$db->connect($infos, $options);
	
	$encoding = $db->encoding();
	if( strncmp($infos['driver'], 'sqlite', 6) != 0 && preg_match('#^UTF-?(8|16)|UCS-?2|UNICODE$#i', $encoding) ) {
		/*
		 * WorkAround : Wanewsletter ne g�re pas les codages de caract�res multi-octets.
		 * Si le jeu de caract�res de la connexion est multi-octet, on le change
		 * arbitrairement pour le latin1 et on affiche une alerte � l'utilisateur.
		 */
		$newEncoding = 'latin1';
		$db->encoding($newEncoding);
		
		wanlog("<p>Wanewsletter a d�tect� que le <strong>jeu de caract�res de connexion</strong>
� votre base de donn�es est r�gl� sur <q>$encoding</q>. Wanewsletter ne g�re
pas les codages de caract�res multi-octets et a donc chang� cette valeur pour
<q>$newEncoding</q>.</p>
<p>Vous devriez �diter le fichier <samp>includes/config.inc.php</samp> et fixer
ce r�glage en ajoutant la cha�ne <code>?charset=latin1</code> apr�s le nom de votre
base de donn�es dans la variable <code>\$dsn</code> (<q>latin1</q> est utilis�
dans l'exemple mais vous pouvez sp�cifier n'importe quel jeu de caract�re 8 bit
convenant le mieux � votre langue. R�f�rez-vous � la documentation de votre base
de donn�es pour conna�tre les jeux de caract�res utilisables).</p>");
	}
	
	return $db;
}

}
?>
