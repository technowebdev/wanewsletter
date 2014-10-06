<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aur�lien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
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
	 * @access private
	 */
	var $user_ip      = '';
	
	/**
	 * Identifiant de la session
	 * 
	 * @var string
	 * @access private
	 */
	var $session_id   = '';
	
	/**
	 * Donn�es de la session
	 * 
	 * @var array
	 * @access private
	 */
	var $sessiondata  = array();
	
	/**
	 * Configuration pour l'envoi des cookies
	 * 
	 * @var array
	 * @access private
	 */
	var $cfg_cookie   = array();
	
	/**
	 * La session vient elle d'�tre cr��e ?
	 * 
	 * @var boolean
	 * @access private
	 */
	var $new_session  = false;
	
	/**
	 * Statut utilisateur connect�/non connect�
	 * 
	 * @var boolean
	 * @access private
	 */
	var $is_logged_in = false;
	
	/**
	 * Mise � jour du hash de mot de passe � chaque identification r�ussie
	 
	 * @var boolean
	 * @access public
	 */
	var $update_hash  = true;
	
	/**
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
		
		if( preg_match('/^\d+\.\d+\.\d+\.\d+/', $proxy_ip, $match) )
		{
			$private_ip = $match[0];
			
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
			$pattern_ip[] = '/^127\..*/'; // ip locale
			
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
		
		$this->cfg_cookie['cookie_name']   = $nl_config['cookie_name'];
		$this->cfg_cookie['cookie_path']   = $nl_config['cookie_path'];
		$this->cfg_cookie['cookie_domain'] = null;
		$this->cfg_cookie['cookie_secure'] = !empty($_SERVER['HTTPS']) ? 1 : 0;
		$this->cfg_cookie['cookie_httponly'] = true;
	}
	
	/**
	 * Ouverture d'une nouvelle session
	 * 
	 * @param array   $admindata    Donn�es utilisateur
	 * @param boolean $autologin    True si activer l'autoconnexion
	 * 
	 * @access public
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
		
		if( $this->session_id == '' || !$db->build(SQL_UPDATE, SESSIONS_TABLE, $sql_data, array('session_id' => $this->session_id))
			|| $db->affectedRows() == 0 )
		{
			$this->new_session = true;
			$this->session_id  = $sql_data['session_id'] = generate_key();
			
			if( !$db->build(SQL_INSERT, SESSIONS_TABLE, $sql_data) )
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
		$this->send_cookie('data', serialize($sessiondata), strtotime('+1 month'));
		
		$this->is_logged_in = true;
		
		return $admindata;
	}
	
	/**
	 * V�rification de la session et de l'utilisateur
	 * 
	 * @param integer $liste    Id de la liste actuellement g�r�e
	 * 
	 * @access public
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
			$sessiondata = '';
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
			$db->query($sql);
		}
		
		if( $this->session_id != '' )
		{
			//
			// R�cup�ration des infos sur la session et l'utilisateur 
			//
			$sql = "SELECT s.*, a.*
				FROM " . SESSIONS_TABLE . " AS s
					INNER JOIN " . ADMIN_TABLE . " AS a ON a.admin_id = s.admin_id
				WHERE s.session_id = '{$this->session_id}'
					AND s.session_start > " . $expiry_time;
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible de r�cup�rer les infos sur la session et l\'utilisateur', CRITICAL_ERROR);
			}
			
			if( $row = $result->fetch() )
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
							$this->send_cookie('listeid', $row['session_liste'], strtotime('+1 month'));
						}
					}
					
					$this->is_logged_in = true;
					
					return $row;
				}
			}
		}
		
		$this->sessiondata['listeid'] = ( !empty($_COOKIE[$this->cfg_cookie['cookie_name'] . '_listeid']) ) ? intval($_COOKIE[$this->cfg_cookie['cookie_name'] . '_listeid']) : 0;
		
		//
		// Connexion automatique 
		//
		$autologin = true;
		
		//
		// Authentification HTTP Basic
		//
		if( ENABLE_HTTP_AUTHENTICATION )
		{
			$username = $passwd = $authorization = null;
			
			if( !empty($_SERVER['PHP_AUTH_USER']) )
			{
				$username = $_SERVER['PHP_AUTH_USER'];
				$passwd   = $_SERVER['PHP_AUTH_PW'];
			}
			
			// Cas particulier : PHP en mode CGI
			else if( !empty($_SERVER['REMOTE_USER']) )
			{
				$authorization = $_SERVER['REMOTE_USER'];
			}
			else if( !empty($_SERVER['REDIRECT_REMOTE_USER']) )// Dans certains cas de redirections internes
			{
				$authorization = $_SERVER['REDIRECT_REMOTE_USER'];
			}
			
			// Cas particulier pour IIS et PHP4, dixit le manuel PHP
			else if( !empty($_SERVER['HTTP_AUTHORIZATION']) )
			{
				$authorization = $_SERVER['HTTP_AUTHORIZATION'];
			}
			
			if( !is_null($authorization) && strncasecmp($authorization, 'Basic ', 6) == 0 )
			{
				list($username, $passwd) = explode(':', base64_decode(substr($authorization, 6)), 2);
			}
			
			if( !is_null($username) )
			{
				$autologin = false;
				$this->sessiondata['adminid'] = $username;
				$this->sessiondata['adminloginkey'] = md5($passwd);
			}
		}
		
		if( !empty($this->sessiondata['adminloginkey']) )
		{
			$admin_id = ( !empty($this->sessiondata['adminid']) ) ? $this->sessiondata['adminid'] : 0;
			
			return $this->login($admin_id, $this->sessiondata['adminloginkey'], $autologin);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * D�connexion de l'administration
	 * 
	 * @param integer $admin_id    Id de l'utilisateur concern�
	 * 
	 * @access public
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
		
		$this->is_logged_in = false;
		$ts_expire = strtotime('+1 month');
		$this->send_cookie('sessid', '', $ts_expire);
		$this->send_cookie('data', '', $ts_expire);
	}
	
	/**
	 * Connexion � l'administration
	 * 
	 * @param mixed   $admin_mixed    Id ou pseudo de l'utilisateur concern�
	 * @param string  $admin_pwd      Mot de passe de l'utilisateur
	 * @param boolean $autologin      True si autoconnexion demand�e
	 * 
	 * @access public
	 * @return mixed
	 */
	function login($admin_mixed, $admin_pwd, $autologin)
	{
		global $db;
		
		$sql = 'SELECT s.*, a.*
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
		
		$login = false;
		$hasher = new PasswordHash();
		
		if( $admindata = $result->fetch() )
		{
			// Ugly old md5 hash prior Wanewsletter 2.4-beta2
			if( $admindata['admin_pwd'][0] != '$' )
			{
				if( $admindata['admin_pwd'] === md5($admin_pwd) )
				{
					$login = true;
				}
			}
			// New password hash using phpass
			else if( $hasher->check($admin_pwd, $admindata['admin_pwd']) )
			{
				$login = true;
			}
		}
		
		if( $login )
		{
			if( $this->update_hash )
			{
				$admindata['admin_pwd'] = $hasher->hash($admin_pwd);
				
				$sql = sprintf("UPDATE %s SET admin_pwd = '%s' WHERE admin_id = %d",
					ADMIN_TABLE, $db->escape($admindata['admin_pwd']), $admindata['admin_id']);
				
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de mettre � jour la table administrateur', CRITICAL_ERROR);
				}
			}
			
			return $this->open($admindata, $autologin);
		}
		
		return false;
	}
	
	/**
	 * Envoi des cookies
	 * 
	 * @param string  $name           Nom du cookie
	 * @param string  $cookie_data    Donn�es � ins�rer dans le cookie
	 * @param integer $cookie_time    Dur�e de validit� du cookie
	 * 
	 * @access public
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
			$this->cfg_cookie['cookie_secure'],
			$this->cfg_cookie['cookie_httponly']
		);
	}
	
	/**
	 * Encodage des IP pour stockage et comparaisons plus simples 
	 * Import� de phpBB et modifi� 
	 * 
	 * @param string $dotquat_ip
	 * 
	 * @access public
	 * @return string
	 */
	function encode_ip($dotquad_ip)
	{
		$ip_sep = explode('.', $dotquad_ip);
		return sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);
	}
	
	/**
	 * D�codage des IP 
	 * Import� de phpBB et modifi� 
	 * 
	 * @param string $hex_ip    Ip en hexad�cimal
	 * 
	 * @access public
	 * @return string
	 */
	function decode_ip($hex_ip)
	{
		$hexip_parts = explode('.', chunk_split($hex_ip, 2, '.'));
		array_pop($hexip_parts);
		
		return implode('.', array_map('hexdec', $hexip_parts));
	}
}

}

