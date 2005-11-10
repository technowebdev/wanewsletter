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

if( !defined('CLASS_SESSION_INC') ) {

define('CLASS_SESSION_INC', true);

/**
 * Class Session
 * 
 * Gestion des connexions � l'administration
 */
class Session {
	
	/**
	 * Ip de l'utilisateur
	 * 
	 * @var string
	 * 
	 * @access private
	 */
	var $user_ip      = '';
	
	/**
	 * Chaine �ventuelle � ajouter � la fin des urls (contient l'identifiant de session)
	 * 
	 * @var string
	 * 
	 * @access private
	 */
	var $sessid_url   = '';
	
	/**
	 * Identifiant de la session
	 * 
	 * @var string
	 * 
	 * @access private
	 */
	var $session_id   = '';
	
	/**
	 * Donn�es de la session
	 * 
	 * @var array
	 * 
	 * @access private
	 */
	var $sessiondata  = array();
	
	/**
	 * Configuration pour l'envoi des cookies
	 * 
	 * @var array
	 * 
	 * @access private
	 */
	var $cfg_cookie   = array();
	
	/**
	 * La session vient elle d'�tre cr��e ?
	 * 
	 * @var boolean
	 * 
	 * @access private
	 */
	var $new_session  = FALSE;
	
	/**
	 * Statut utilisateur connect�/non connect�
	 * 
	 * @var boolean
	 * 
	 * @access private
	 */
	var $is_logged_in = FALSE;
	
	/**
	 * Session::session()
	 * 
	 * Intialisation de la classe, r�cup�ration de l'ip ..
	 * 
	 * @return void
	 */
	function session()
	{
		global $nl_config;
		
		//
		// R�cup�ration de l'IP 
		//
		$client_ip = server_info('REMOTE_ADDR');
		$proxy_ip  = server_info('HTTP_X_FORWARDED_FOR');
		
		if( empty($client_ip) )
		{
			$client_ip = '127.0.0.1';
		}
		
		if( !empty($proxy_ip) && preg_match('/^(\d+\.\d+\.\d+\.\d+)/', $proxy_ip, $match) )
		{
			$private_ip = trim($match[1]);
			
			/*
			 * Liens utiles sur les diff�rentes plages d'ip : 
			 * 
			 * @link http://www.commentcamarche.net/internet/ip.php3 
			 * @link http://www.usenet-fr.net/fur/comp/reseaux/masques.html 
			 */	 
			
			//
			// Liste d'ip non valides 
			//
			$pattern_ip = array();
			$pattern_ip[] = '/^0\..*/'; // R�seau 0 n'existe pas 
			$pattern_ip[] = '/^127\.0\.0\.1/'; // ip locale 
			
			// Plages d'ip sp�cifiques � l'intranet 
			$pattern_ip[] = '/^10\..*/';
			$pattern_ip[] = '/^172\.1[6-9]\..*/';
			$pattern_ip[] = '/^172\.2[0-9]\..*/';
			$pattern_ip[] = '/^172\.3[0-1]\..*/';
			$pattern_ip[] = '/^192\.168\..*/';
			
			// Plage d'adresse de classe D r�serv�e pour les flux multicast et de classe E, non utilis�e 
			$pattern_ip[] = '/^22[4-9]\..*/';
			$pattern_ip[] = '/^2[3-5][0-9]\..*/';
			
			$client_ip = preg_replace($pattern_ip, $client_ip, $private_ip);
		}
		
		$this->user_ip = $this->encode_ip($client_ip);
		
		preg_match('/^http(s)?:\/\/(.*?)\/?$/i', $nl_config['urlsite'], $match);
		
		$this->cfg_cookie['cookie_name']   = $nl_config['cookie_name'];
		$this->cfg_cookie['cookie_path']   = $nl_config['cookie_path'];
		$this->cfg_cookie['cookie_domain'] = '';//$match[2];
		$this->cfg_cookie['cookie_secure'] = ( !empty($match[1]) ) ? 1 : 0;
	}
	
	/**
	 * Session::open()
	 * 
	 * Ouverture d'une nouvelle session
	 * 
	 * @param array   $admindata    Donn�es utilisateur
	 * @param boolean $autologin    True si activer l'autoconnexion
	 * 
	 * @return array
	 */
	function open($admindata, $autologin)
	{
		global $db;
		
		$current_time = time();
		$liste = ( !empty($this->sessiondata['listeid']) ) ? $this->sessiondata['listeid'] : 0;
		
		if( !empty($admindata['session_id']) )
		{
			$this->session_id = $admindata['session_id'];
		}
		
		$sql_data = array(
			'admin_id'      => $admindata['admin_id'],
			'session_start' => $current_time,
			'session_time'  => $current_time,
			'session_ip'    => $this->user_ip,
			'session_liste' => $liste
		);
		
		if( $this->session_id == '' || !$db->query_build('UPDATE', SESSIONS_TABLE, $sql_data, array('session_id' => $this->session_id))
			|| $db->affected_rows() == 0 )
		{
			$this->new_session = TRUE;
			$this->session_id  = $sql_data['session_id'] = generate_key();
			
			if( !$db->query_build('INSERT', SESSIONS_TABLE, $sql_data) )
			{
				trigger_error('Impossible de d�marrer une nouvelle session', CRITICAL_ERROR);
			}
		}
		
		$admindata = array_merge($admindata, $sql_data);
		
		$sessiondata = array(
			'adminloginkey' => ( $autologin ) ? $admindata['admin_pwd'] : '',
			'adminid' => $admindata['admin_id']
		);
		
		$this->send_cookie('sessid', $this->session_id, 0);
		$this->send_cookie('data', serialize($sessiondata), $current_time + 31536000);
		
		$this->sessid_url   = 'sessid=' . $this->session_id;
		$this->is_logged_in = TRUE;
		
		return $admindata;
	}
	
	/**
	 * Session::check()
	 * 
	 * V�rification de la session et de l'utilisateur
	 * 
	 * @param integer $liste    Id de la liste actuellement g�r�e
	 * 
	 * @return mixed
	 */ 
	function check($liste = 0)
	{
		global $db, $nl_config;
		
		if( !empty($_COOKIE[$this->cfg_cookie['cookie_name'] . '_sessid']) || !empty($_COOKIE[$this->cfg_cookie['cookie_name'] . '_data']) )
		{
			$this->session_id = ( !empty($_COOKIE[$this->cfg_cookie['cookie_name'] . '_sessid']) ) ? $_COOKIE[$this->cfg_cookie['cookie_name'] . '_sessid'] : '';
			$sessiondata = ( !empty($_COOKIE[$this->cfg_cookie['cookie_name'] . '_data']) ) ? unserialize($_COOKIE[$this->cfg_cookie['cookie_name'] . '_data']) : '';
		}
		else
		{
			$this->session_id = ( !empty($_GET['sessid']) ) ? $_GET['sessid'] : '';
			$sessiondata = '';
			
			if( $this->session_id != '' )
			{
				$this->sessid_url = 'sessid=' . $this->session_id;
			}
		}
		
		$current_time = time();
		$expiry_time  = ($current_time - $nl_config['session_length']);
		$this->sessiondata = ( is_array($sessiondata) ) ? $sessiondata : array();
		
		//
		// Suppression des sessions p�rim�es 
		//
		if( !($current_time % 5) )
		{
			$sql = "DELETE FROM " . SESSIONS_TABLE . "
				WHERE session_time < $expiry_time
					AND session_id != '{$this->session_id}'";
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer les sessions p�rim�es', CRITICAL_ERROR);
			}
		}
		
		if( $this->session_id != '' )
		{
			//
			// R�cup�ration des infos sur la session et l'utilisateur 
			//
			$sql = "SELECT s.session_id, s.session_start, s.session_time, s.session_ip, s.session_liste,
					a.admin_id, a.admin_login, a.admin_pwd, a.admin_email, a.admin_lang, a.admin_dateformat,
					a.admin_level, a.email_new_inscrit
				FROM " . SESSIONS_TABLE . " AS s
					INNER JOIN " . ADMIN_TABLE . " AS a
					ON a.admin_id = s.admin_id
				WHERE s.session_id = '{$this->session_id}'
					AND s.session_start > " . $expiry_time;
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible de r�cup�rer les infos sur la session et l\'utilisateur', CRITICAL_ERROR);
			}
			
			if( $row = $db->fetch_array($result) )
			{
				//
				// Comparaison des ip pour �viter la substitution des sessions 
				// Peut poser probl�me avec certains proxy 
				//
				$len_check_ip = 4;
				
				if( strncasecmp($row['session_ip'], $this->user_ip, $len_check_ip) == 0 )
				{
					$force_update = false;
					if( ( $liste > 0 && $liste != $row['session_liste'] ) || $liste == -1 )
					{
						$force_update = true;
						$row['session_liste'] = ( $liste == -1 ) ? 0 : $liste;
					}
					
					if( ($current_time - $row['session_time']) > 60 || $force_update )
					{
						$sql = "UPDATE " . SESSIONS_TABLE . " 
							SET session_time  = $current_time, 
								session_liste = $row[session_liste]
							WHERE session_id = '{$this->session_id}'";
						if( !$db->query($sql) )
						{
							trigger_error('Impossible de mettre � jour la session en cours', CRITICAL_ERROR);
						}
						
						if( $force_update )
						{
							$this->send_cookie('listeid', $row['session_liste'], $current_time + 31536000);
						}
					}
					
					$this->is_logged_in = TRUE;
					
					return $row;
				}
			}
		}
		
		$this->sessiondata['listeid'] = ( !empty($_COOKIE[$this->cfg_cookie['cookie_name'] . '_listeid']) ) ? intval($_COOKIE[$this->cfg_cookie['cookie_name'] . '_listeid']) : 0;
		
		//
		// Connexion automatique 
		//
		
		if( !empty($this->sessiondata['adminloginkey']) )
		{
			$admin_id = ( !empty($this->sessiondata['adminid']) ) ? intval($this->sessiondata['adminid']) : 0;
			
			return $this->login($admin_id, $this->sessiondata['adminloginkey'], TRUE);
		}
		else
		{
			$this->send_cookie('sessid', '', $current_time - 31536000);
			$this->send_cookie('data', '', $current_time - 31536000);
			
			return false;
		}
	}
	
	/**
	 * Session::logout()
	 * 
	 * D�connexion de l'administration
	 * 
	 * @param integer $admin_id    Id de l'utilisateur concern�
	 * 
	 * @return void
	 */
	function logout($admin_id)
	{
		global $db;
		
		$current_time = time();
		
		if( $this->session_id != '' )
		{
			$sql = "DELETE FROM " . SESSIONS_TABLE . " 
				WHERE session_id = '{$this->session_id}'
					AND admin_id = " . $admin_id;
			if( !$db->query($sql) )
			{
				trigger_error('Erreur lors de la fermeture de la session', CRITICAL_ERROR);
			}
		}
		
		$this->send_cookie('sessid', '', $current_time - 31536000);
		$this->send_cookie('data', '', $current_time - 31536000);
	}
	
	/**
	 * Session::login()
	 * 
	 * Connexion � l'administration
	 * 
	 * @param mixed   $admin_mixed    Id ou pseudo de l'utilisateur concern�
	 * @param string  $admin_pwd      Mot de passe de l'utilisateur
	 * @param boolean $autologin      True si autoconnexion demand�e
	 * 
	 * @return mixed
	 */
	function login($admin_mixed, $admin_pwd, $autologin)
	{
		global $db;
		
		$sql = 'SELECT s.session_id, s.session_start, s.session_time, s.session_ip, s.session_liste,
					a.admin_id, a.admin_login, a.admin_pwd, a.admin_email, a.admin_lang, a.admin_dateformat,
					a.admin_level, a.email_new_inscrit
			FROM ' . ADMIN_TABLE . ' AS a
			LEFT JOIN ' . SESSIONS_TABLE . ' AS s ON s.admin_id = a.admin_id WHERE ';
		if( is_numeric($admin_mixed) )
		{
			$sql .= 'a.admin_id = ' . $admin_mixed;
		}
		else
		{
			$sql .= 'LOWER(a.admin_login) = \'' . $db->escape(strtolower($admin_mixed)) . '\'';
		}
		
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les donn�es sur cet utilisateur', CRITICAL_ERROR);
		}
		
		if( ($admindata = $db->fetch_array($result)) && $admindata['admin_pwd'] == $admin_pwd )
		{
			return $this->open($admindata, $autologin);
		}
		
		return false;
	}
	
	/**
	 * Session::send_cookie()
	 * 
	 * Envoi des cookies
	 * 
	 * @param string  $name           Nom du cookie
	 * @param string  $cookie_data    Donn�es � ins�rer dans le cookie
	 * @param integer $cookie_time    Dur�e de validit� du cookie
	 * 
	 * @return void
	 */
	function send_cookie($name, $cookie_data, $cookie_time)
	{
		setcookie(
			$this->cfg_cookie['cookie_name'] . '_' . $name,
			$cookie_data,
			$cookie_time,
			$this->cfg_cookie['cookie_path'],
			$this->cfg_cookie['cookie_domain'],
			$this->cfg_cookie['cookie_secure']
		);
	}
	
	/**
	 * Session::encode_ip()
	 * 
	 * Encodage des IP pour stockage et comparaisons plus simples 
	 * Import� de phpBB et modifi� 
	 * 
	 * @param string $dotquat_ip
	 * 
	 * @return string
	 */
	function encode_ip($dotquad_ip)
	{
		$ip_sep = explode('.', $dotquad_ip);
		return sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);
	}
	
	/**
	 * Session::decode_ip()
	 * 
	 * D�codage des IP 
	 * Import� de phpBB et modifi� 
	 * 
	 * @param string $hex_ip    Ip en hexad�cimal
	 * 
	 * @return string
	 */
	function decode_ip($hex_ip)
	{
		$hexip_parts = explode('.', chunk_split($hex_ip, 2, '.'));
		array_pop($hexip_parts);
		
		return implode('.', array_map('hexdec', $hexip_parts));
	}
}

/**
 * sessid()
 * 
 * Ajout de l'identifiant de session dans l'url si les cookies sont refus�s
 * 
 * @param string  $var       Url, texte (si $is_str � true)
 * @param boolean $is_str    True si on doit scanner du texte et rechercher les endroits o� ajouter l'id de session
 * 
 * @return string
 */
function sessid($var, $is_str = false)
{
	global $session;
	
	if( $session->sessid_url != '' )
	{
		if( $is_str )
		{
			$var = preg_replace('/(action|a href)="(?!ftp|http|mailto|javascript|{)([^"]+)"/e', '\'\\1="\' . sessid(\'\\2\') . \'"\'', $var);
		}
		else if( !preg_match('/sessid=[[:alnum:]]+/', $var) )
		{
			$var .= ( ( strpos($var, '?') ) ? '&amp;' : '?' ) . $session->sessid_url;
		}
	}
	
	return $var;
}

}
?>