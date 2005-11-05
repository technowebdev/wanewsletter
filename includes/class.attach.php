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

if( !defined('CLASS_ATTACH_INC') ) {

define('CLASS_ATTACH_INC', true);

/**
 * Class Attach
 * 
 * Gestion des fichiers joints des newsletters
 */ 
class Attach {
	
	/**
	 * Chemin vers le dossier de stockage des fichiers
	 * 
	 * @var string
	 */
	var $upload_path = '';
	
	/**
	 * Utilisation ou non de l'option ftp
	 * 
	 * @var boolean
	 */
	var $use_ftp     = FALSE;
	
	/**
	 * Chemin vers le dossier de stockage des fichiers sur le ftp
	 * 
	 * @var string
	 */
	var $ftp_path    = '';
	
	/**
	 * Identifiant de ressource au serveur ftp
	 * 
	 * @var resource
	 */
	var $connect_id  = NULL;
	
	/**
	 * Attach::Attach()
	 * 
	 * Initialisation des variables de la classe
	 * Initialisation de la connexion au serveur ftp le cas �ch�ant
	 * 
	 * @return void
	 */
	function Attach()
	{
		global $nl_config;
		
		$this->upload_path = WA_ROOTDIR . '/' . $nl_config['upload_path'];
		$this->use_ftp     = $nl_config['use_ftp'];
		
		if( $this->use_ftp )
		{
			$result = $this->connect_to_ftp(
				$nl_config['ftp_server'],
				$nl_config['ftp_port'],
				$nl_config['ftp_user'],
				$nl_config['ftp_pass'],
				$nl_config['ftp_pasv'],
				$nl_config['ftp_path']
			);
			
			if( $result['error'] )
			{
				trigger_error($result['message'], ERROR);
			}
			
			$this->connect_id = $result['connect_id'];
			$this->ftp_path   = $nl_config['ftp_path'];
		}
	}
	
	/**
	 * Attach::connect_to_ftp()
	 * 
	 * Fonction de connexion au serveur ftp
	 * La fonction a �t� affranchi de fa�on � �tre utilisable sans cr�er 
	 * une instance de la classe. (pour tester la connexion dans la config. g�n�rale)
	 * 
	 * @param string  $ftp_server    Nom du serveur ftp
	 * @param integer $ftp_port      Port de connexion
	 * @param string  $ftp_user      Nom d'utilisateur si besoin
	 * @param string  $ftp_pass      Mot de passe si besoin
	 * @param integer $ftp_pasv      Mode actif ou passif
	 * @param string  $ftp_path      Chemin vers le dossier des fichiers joints
	 * 
	 * @return array
	 */
	function connect_to_ftp($ftp_server, $ftp_port, $ftp_user, $ftp_pass, $ftp_pasv, $ftp_path)
	{
		if( !($connect_id = @ftp_connect($ftp_server, $ftp_port)) )
		{
			return array('error' => true, 'message' => 'Ftp_unable_connect');
		}
		
		if( $ftp_user != '' && $ftp_pass != '' )
		{
			if( !@ftp_login($connect_id, $ftp_user, $ftp_pass) )
			{
				return array('error' => true, 'message' => 'Ftp_error_login');
			}
		}
		
		if( !@ftp_pasv($connect_id, $ftp_pasv) )
		{
			return array('error' => true, 'message' => 'Ftp_error_mode');
		}
		
		if( !@ftp_chdir($connect_id, $ftp_path) )
		{
			return array('error' => true, 'message' => 'Ftp_error_path');
		}
		
		return array('error' => false, 'connect_id' => $connect_id);
	}
		
	/**
	 * Attach::joined_file_exists()
	 * 
	 * Verifie la pr�sence du fichier demand� dans le dossier des fichier joints ou sur le ftp
	 * 
	 * @param string  $filename    Nom du fichier
	 * @param boolean $error       True si une erreur s'est produite
	 * @param array   $msg_error   Tableau des erreurs
	 * 
	 * @return integer
	 */
	function joined_file_exists($filename, &$error, &$msg_error)
	{
		global $lang;
		
		$file_exists = false;
		$filesize    = 0;
		
		if( $this->use_ftp )
		{
			$listing = @ftp_rawlist($this->connect_id, $this->ftp_path);
			
			if( is_array($listing) && count($listing) )
			{
				//
				// On v�rifie chaque entr�e du listing pour retrouver le fichier sp�cifi�
				//
				foreach( $listing AS $line_info )
				{
					if( preg_match('/^\s*([d-])[rwxst-]{9} .+ ([0-9]*) [a-zA-Z]+ [0-9:\s]+ (.+)$/i', $line_info, $matches) )
					{
						if( $matches[1] != 'd' && $matches[3] == $filename )
						{
							$file_exists = true;
							$filesize    = $matches[2];
							
							break;
						}
					}
				}
			}
		}
		else if( file_exists(wa_realpath($this->upload_path . $filename)) )
		{
			$file_exists = true;
			$filesize    = filesize(wa_realpath($this->upload_path . $filename));
		}
		
		if( !$file_exists )
		{
			$error = TRUE;
			$msg_error[] = sprintf($lang['Message']['File_not_exists'], '');
		}
		
		return $filesize;
	}
		
	/**
	 * Attach::make_filename()
	 * 
	 * G�n�ration d'un nom de fichier unique
	 * Fonction r�cursive
	 * 
	 * @param string $prev_physical_filename    Nom du fichier temporaire pr�c�demment g�n�r� et refus�
	 * 
	 * @return string
	 */
	function make_filename($prev_physical_filename = '')
	{
		global $db;
		
		$physical_filename = md5( uniqid( rand() ) ) . '.dl';
		
		if( $physical_filename != $prev_physical_filename )
		{
			$sql = "SELECT COUNT(file_id) AS test_name 
				FROM " . JOINED_FILES_TABLE . " 
				WHERE file_physical_name = '$physical_filename'";
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible de tester la table des fichiers joints', ERROR);
			}
			
			$test_name = $db->result($result, 0, 'test_name');
		}
		else
		{
			$test_name = true;
		}
		
		return ( $test_name ) ? $this->make_filename($physical_filename) : $physical_filename;
	}
	
	/**
	 * Attach::upload_file()
	 * 
	 * Effectue les v�rifications n�cessaires et ajoute une entr�e dans les tables de 
	 * gestion des fichiers joints
	 * 
	 * Le fichier peut �tre upload� via le formulaire ad�quat, �tre sur un serveur distant, 
	 * ou avoir �t� upload� manuellement sur le serveur
	 * 
	 * @param string  $upload_mode    Mode d'upload du fichier (upload http, � distance, fichier local)
	 * @param integer $log_id         Identifiant du log
	 * @param string  $filename       Nom du fichier
	 * @param string  $tmp_filename   Nom temporaire du fichier/nom du fichier local/url du fichier distant
	 * @param integer $filesize       Taille du fichier
	 * @param string  $filetype       Type mime du fichier
	 * @param string  $errno_code     Code erreur �ventuel de l'upload http
	 * @param boolean $error          True si une erreur survient
	 * @param array   $msg_error      Tableau des messages d'erreur
	 * 
	 * @return void
	 */
	function upload_file($upload_mode, $log_id, $filename, $tmp_filename, $filesize, $filetype, $errno_code, &$error, &$msg_error)
	{
		global $db, $lang, $nl_config;
		
		$extension = substr($filename, (strrpos($filename, '.') + 1));
		
		if( $extension == '' )
		{
			$extension = 'wa';
		}
		
		//
		// V�rification de la validit� du nom du fichier
		//
		if( !$this->check_filename($filename) )
		{
			$error = TRUE;
			$msg_error[] = $lang['Message']['Invalid_filename'];
		}
		
		//
		// V�rification de l'extension du fichier
		//
		if( !$this->check_extension($extension) )
		{
			$error = TRUE;
			$msg_error[] = $lang['Message']['Invalid_ext'];
		}
		
		if( !$error )
		{
			//
			// Si l'upload a �chou�, on r�cup�re le message correspondant � l'erreur survenue
			// Voir fichier constantes.php pour les codes d'erreur
			//
			if( $upload_mode == 'upload' && !is_uploaded_file($tmp_filename) )
			{
				$error = TRUE;
				
				switch( $errno_code )
				{
					case UPLOAD_ERR_INI_SIZE:
						$msg_error[] = $lang['Message']['Upload_error_1'];
						break;
					
					case UPLOAD_ERR_FORM_SIZE:
						$msg_error[] = $lang['Message']['Upload_error_2'];
						break;
					
					case UPLOAD_ERR_PARTIAL:
						$msg_error[] = $lang['Message']['Upload_error_3'];
						break;
					
					case UPLOAD_ERR_NO_FILE:
						$msg_error[] = $lang['Message']['Upload_error_4'];
						break;
					
					default:
						$msg_error[] = $lang['Message']['Upload_error_5'];
						break;
				}
				
				return;
			}
			
			//
			// R�cup�ration d'un fichier distant
			//
			else if( $upload_mode == 'remote' )
			{
				$parts = parse_url($tmp_filename);
				if( ($parts['scheme'] != 'http' && ($parts['scheme'] != 'ftp' || !is_available_extension('ftp'))) || empty($parts['host']) || empty($parts['path']) )
				{
					$error = TRUE;
					$msg_error[] = $lang['Message']['Invalid_url'];
					
					return;
				}
				
				$tmp_path = ( OPEN_BASEDIR_RESTRICTION ) ? WA_TMPDIR : '/tmp';
				$tmp_filename = tempnam($tmp_path, uniqid(rand()) . 'wa0');
				
				if( !($fw = @fopen($tmp_filename, 'wb')) )
				{
					$error = TRUE;
					$msg_error[] = $lang['Message']['Upload_error_5'];
					
					return;
				}
				
				if( $parts['scheme'] == 'http' )
				{
					if( !($fsock = @fsockopen($parts['host'], (!empty($parts['port'])) ? $parts['port'] : 80, $errno, $errstr, 5)) )
					{
						$error = TRUE;
						$msg_error[] = sprintf($lang['Message']['Unaccess_url'], htmlspecialchars($tmp_filename));
						
						return;
					}
					
					@fputs($fsock, "GET $parts[path]" . (!empty($parts['query']) ? '?' . $parts['query'] : '') . " HTTP/1.1\r\n");
					@fputs($fsock, "Host: $parts[host]\r\n");
					@fputs($fsock, "Accept: */*\r\n");
					@fputs($fsock, 'User-Agent: Wanewsletter ' . $nl_config['version'] . "\r\n");
					@fputs($fsock, "Connection: close\r\n\r\n");
					
					//
					// Ok, le fichier est bien pr�sent, on r�cup�re le reste des donn�es
					//
					$data = '';
					while( !@feof($fsock) )
					{
						$data .= @fread($fsock, 512);
					}
					fclose($fsock);
					
					list($headers, $data) = explode("\r\n\r\n", $data, 3);
					$headers = str_replace("\r\n", "\n", $headers);
					list($response) = explode("\n", $headers, 2);
					
					//
					// Si le code r�ponse est diff�rent de 200, le fichier n'est pas ou plus � l'emplacement indiqu�
					//
					if( !strstr($response, '200') )
					{
						$error = TRUE;
						$msg_error[] = $lang['Message']['Not_found_at_url'];
						
						return;
					}
					
					//
					// Recherche de la taille des donn�es
					//
					if( !preg_match('/^Content-Length:[[:space:]]*([0-9]+)$/mi', $headers, $match) )
					{
						$error = TRUE;
						$msg_error[] = $lang['Message']['No_data_at_url'] . ' (taille manquante)';
						
						return;
					}
					
					$filesize = $match[1];
					
					//
					// Recherche du type mime des donn�es
					//
					if( !preg_match('/^Content-Type:[[:space:]]*([a-z]+\/[a-z0-9+.-]+)$/mi', $headers, $match) )
					{
						$error = TRUE;
						$msg_error[] = $lang['Message']['No_data_at_url'] . ' (type manquant)';
						
						return;
					}
					
					$filetype = $match[1];
					
					fwrite($fw, $data);
				}
				else
				{
					if( empty($parts['user']) )
					{
						$parts['user'] = 'anonymous';
					}
					if( empty($parts['pass']) )
					{
						$parts['pass'] = 'anonymous';
					}
					
					if( !($cid = @ftp_connect($parts['host'], (!empty($parts['port'])) ? $parts['port'] : 21)) || !@ftp_login($cid, $parts['user'], $parts['pass']) )
					{
						$error = TRUE;
						$msg_error[] = sprintf($lang['Message']['Unaccess_url'], htmlspecialchars($tmp_filename));
						
						return;
					}
					
					$filesize = ftp_size($cid, $parts['path']);
					
					if( !ftp_fget($cid, $fw, $parts['path'], FTP_BINARY) )
					{
						$error = TRUE;
						$msg_error[] = $lang['Message']['Not_found_at_url'];
						
						return;
					}
					ftp_quit($cid);
					
					require WAMAILER_DIR . '/class.mailer.php';
					
					$filetype = Mailer::mime_type(substr($filename, (strrpos($filename, '.') + 1)));
				}
				
				fclose($fw);
			}
			
			//
			// Fichier upload� manuellement sur le serveur
			//
			else if( $upload_mode == 'local' )
			{
				require WAMAILER_DIR . '/class.mailer.php';
				
				$filetype = Mailer::mime_type($extension);
				
				//
				// On verifie si le fichier est bien pr�sent sur le serveur
				//
				$filesize = $this->joined_file_exists($tmp_filename, $error, $msg_error);
			}
		}
		else
		{
			return; 
		}
		
		//
		// V�rification de la taille du fichier par rapport � la taille maximale autoris�e
		//
		$total_size = 0;
		if( !$this->check_maxsize($log_id, $filesize, $total_size) )
		{
			$error = TRUE;
			$msg_error[] = sprintf($lang['Message']['weight_too_big'], ($nl_config['max_filesize'] - $total_size));
		}
		
		//
		// Si fichier upload� ou fichier distant, on d�place le fichier � son emplacement final
		//
		if( !$error && $upload_mode != 'local' )
		{
			$physical_filename = $this->make_filename();
			
			if( $this->use_ftp )
			{
				$mode = $this->get_mode($filetype);
				
				if( !@ftp_put($this->connect_id, $physical_filename, $tmp_filename, $mode) )
				{
					$error = TRUE;
					$msg_error[] = $lang['Message']['Ftp_error_put'];
				}
				else
				{
					@ftp_site($this->connect_id, 'CHMOD 0644 ' . $physical_filename);
				}
			}
			else
			{
				if( $upload_mode == 'remote' )
				{
					$result_upload = @copy($tmp_filename, $this->upload_path . $physical_filename);
				}
				else
				{
					$result_upload = @move_uploaded_file($tmp_filename, $this->upload_path . $physical_filename);
				}
				
				if( !$result_upload )
				{
					$error = TRUE;
					$msg_error[] = $lang['Message']['Upload_error_5'];
				}
				
				if( !$error )
				{
					@chmod($this->upload_path . $physical_filename, 0644);
				}
			}
			
			//
			// Suppression du fichier temporaire cr�� par nos soins
			//
			if( OPEN_BASEDIR_RESTRICTION )
			{
				$this->remove_file($tmp_filename);
			}
		}
		
		if( !$error )
		{
			//
			// Tout s'est bien pass�, on entre les nouvelles donn�es dans la base de donn�es
			//
			$filedata = array(
				'file_real_name'     => $filename,
				'file_physical_name' => ( $upload_mode == 'local' ) ? $tmp_filename : $physical_filename,
				'file_size'          => $filesize,
				'file_mimetype'      => $filetype
			);
			
			if( !$db->query_build('INSERT', JOINED_FILES_TABLE, $filedata) )
			{
				trigger_error('Impossible d\'ins�rer les donn�es du fichier dans la base de donn�es', ERROR);
			}
			
			$file_id = $db->next_id();
			
			$sql = "INSERT INTO " . LOG_FILES_TABLE . " (log_id, file_id) 
				VALUES($log_id, $file_id)";
			if( !$db->query($sql) )
			{
				trigger_error('Impossible d\'ins�rer la jointure dans la table log_files', ERROR);
			}
		}
		
		$this->quit();
	}
	
	/**
	 * Attach::use_file_exists()
	 * 
	 * Ajoute une entr�e pour le log courant avec l'identifiant d'un fichier existant
	 * 
	 * @param integer $file_id     Identifiant du fichier
	 * @param integer $log_id      Identifiant du log
	 * @param boolean $error       True si erreur
	 * @param array	  $msg_error   Tableau des messages d'erreur
	 * 
	 * @access public
	 * 
	 * @return void
	 */
	function use_file_exists($file_id, $log_id, &$error, &$msg_error)
	{
		global $db, $nl_config, $lang, $listdata;
		
		$sql = "SELECT f.file_physical_name 
			FROM " . JOINED_FILES_TABLE . " AS f, " . LOG_FILES_TABLE . " AS lf, " . LOG_TABLE . " AS l 
			WHERE f.file_id = $file_id 
				AND lf.file_id = f.file_id 
				AND lf.log_id = l.log_id 
				AND l.liste_id = " . $listdata['liste_id'];
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible de r�cup�rer les donn�es sur ce fichier', ERROR);
		}
		
		if( $row = $db->fetch_array($result) )
		{
			$physical_name = $row['file_physical_name'];
		}
		else
		{
			$error = TRUE;
			$msg_error[] = sprintf($lang['Message']['File_not_exists'], '');
		}
		
		if( !$error )
		{
			//
			// On verifie si le fichier est bien pr�sent sur le serveur
			//
			$filesize = $this->joined_file_exists($physical_name, $error, $msg_error);
		}
		
		$total_size = 0;
		if( !$error && !$this->check_maxsize($log_id, $filesize, $total_size) )
		{
			$error = TRUE;
			$msg_error[] = sprintf($lang['Message']['weight_too_big'], ($nl_config['max_filesize'] - $total_size));
		}
		
		//
		// Insertion des donn�es
		//
		if( !$error )
		{
			$sql = "INSERT INTO " . LOG_FILES_TABLE . " (log_id, file_id) 
				VALUES($log_id, $file_id)";
			if( !$db->query($sql) )
			{
				trigger_error('Impossible d\'ins�rer la jointure dans la table log_files', ERROR);
			}
		}
		
		$this->quit();
	}
	
	/**
	 * Attach::check_filename()
	 * 
	 * V�rification de la validit� du nom de fichier
	 * 
	 * @param string $filename    Nom du fichier
	 * 
	 * @return boolean
	 */
	function check_filename($filename)
	{
		return ( preg_match('/[\\:*\/?<">|]/', $filename) ) ? false : true;
	}
	
	/**
	 * Attach::check_extension()
	 * 
	 * V�rification de la validit� de l'extension du fichier
	 * 
	 * @param string $extension   Extension du fichier
	 * 
	 * @return integer
	 */
	function check_extension($extension)
	{
		global $db, $listdata;
		
		$sql = "SELECT COUNT(fe_id) AS test_extension 
			FROM " . FORBIDDEN_EXT_TABLE . " 
			WHERE fe_ext = '" . $db->escape($extension) . "' 
				AND liste_id = " . $listdata['liste_id'];
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible de tester la table des extensions interdites',  ERROR);
		}
		
		return ( $db->result($result, 0, 'test_extension') ) ? false : true;
	}
	
	/**
	 * Attach::check_maxsize()
	 * 
	 * V�rification de la taille du fichier par rapport � la taille du log et la taille maximale
	 * 
	 * @param integer $log_id        Identifiant du log
	 * @param integer $filesize      Taille du fichier
	 * @param integer $total_size    Taille totale du log
	 * 
	 * @return boolean
	 */
	function check_maxsize($log_id, $filesize, &$total_size)
	{
		global $db, $nl_config;
		
		$sql = "SELECT SUM(jf.file_size) AS total_size 
			FROM " . JOINED_FILES_TABLE . " AS jf, " . LOG_FILES_TABLE . " AS lf 
			WHERE jf.file_id = lf.file_id AND lf.log_id = " . $log_id;
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir la somme du poids des fichiers joints', ERROR);
		}
		
		$total_size = $db->result($result, 0, 'total_size');
		
		return ( ($total_size + $filesize) > $nl_config['max_filesize'] ) ? false : true;
	}
	
	/**
	 * Attach::download_file()
	 * 
	 * R�cup�re les infos sur le fichier joint � t�l�charger (envoyer au client)
	 * 
	 * @param integer $file_id    Identifiant du fichier joint
	 * 
	 * @return void
	 */
	function download_file($file_id)
	{
		global $db, $listdata, $lang;
		
		$sql = "SELECT f.file_real_name, f.file_physical_name, f.file_size, f.file_mimetype 
			FROM " . JOINED_FILES_TABLE . " AS f, " . LOG_FILES_TABLE . " AS lf, " . LOG_TABLE . " AS l 
			WHERE f.file_id = $file_id 
				AND lf.file_id = f.file_id 
				AND lf.log_id = l.log_id 
				AND l.liste_id = " . $listdata['liste_id'];
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les donn�es sur ce fichier', ERROR);
		}
		
		if( $row = $db->fetch_array($result) )
		{
			if( $this->use_ftp )
			{
				$tmp_filename = $this->ftp_to_tmp($row);
			}
			else
			{
				$tmp_filename = wa_realpath($this->upload_path . $row['file_physical_name']);
			}
			
			if( !($fp = @fopen($tmp_filename, 'rb')) )
			{
				trigger_error('Impossible de r�cup�rer le contenu du fichier (fichier non accessible en lecture)', ERROR);
			}
			
			$data = '';
			while( !@feof($fp) )
			{
				$data .= fread($fp, 1024);
			}
			fclose($fp);
			
			if( $this->use_ftp && OPEN_BASEDIR_RESTRICTION )
			{
				$this->remove_file($tmp_filename);
			}
			
			$this->quit();
			$this->send_file($row['file_real_name'], $row['file_mimetype'], $data, $row['file_size']);
		}
		
		trigger_error(sprintf($lang['Message']['File_not_exists'], ''), MESSAGE);
	}
	
	/**
	 * Attach::ftp_to_tmp()
	 * 
	 * D�placement du fichier demand� du serveur ftp vers le dossier temporaire
	 * Retourne le nom du fichier temporaire
	 * 
	 * @param array $data    Donn�es du fichier joint
	 * 
	 * @return string
	 */
	function ftp_to_tmp($data)
	{
		$mode         = $this->get_mode($data['file_mimetype']);
		$tmp_path     = ( OPEN_BASEDIR_RESTRICTION ) ? WA_TMPDIR : '/tmp';
		$tmp_filename = tempnam($tmp_path, uniqid(rand()) . 'wa1');
		
		if( !@ftp_get($this->connect_id, $tmp_filename, $data['file_physical_name'], $mode) )
		{
			trigger_error('Ftp_error_get', ERROR);
		}
		
		return $tmp_filename;
	}
	
	/**
	 * Attach::get_mode()
	 * 
	 * Mode � utiliser pour le ftp, ascii ou binaire
	 * 
	 * @param string $mime_type : Type mime du fichier concern�
	 * 
	 * @return integer
	 */
	function get_mode($mime_type)
	{
		return ( preg_match('/text|html|xml/i', $mime_type) ) ? FTP_ASCII : FTP_BINARY;// TODO
	}
	
	/**
	 * Attach::delete_joined_files()
	 * 
	 * Fonction de suppression de fichiers joints
	 * Retourne le nombre des fichiers supprim�s, en cas de succ�s
	 * 
	 * @param boolean $massive_delete    Si true, suppression des fichiers joints du ou des logs concern�s
	 * @param mixed   $log_id_ary        id ou tableau des id des logs concern�s
	 * @param mixed   $file_id_ary       id ou tableau des id des fichiers joints concern�s (si $massive_delete � false)
	 * 
	 * @return mixed
	 */
	function delete_joined_files($massive_delete, $log_ids, $file_ids = array())
	{
		global $db;
		
		if( !is_array($log_ids) )
		{
			$log_ids = array($log_ids);
		}
		
		if( !is_array($file_ids) )
		{
			$file_ids = array($file_ids);
		}
		
		if( count($log_ids) > 0 )
		{
			if( $massive_delete )
			{
				$sql = "SELECT file_id 
					FROM " . LOG_FILES_TABLE . " 
					WHERE log_id IN(" . implode(', ', $log_ids) . ") 
					GROUP BY file_id";
				if( !($result = $db->query($sql)) )
				{
					trigger_error('Impossible d\'obtenir la liste des fichiers', ERROR);
				}
				
				$file_ids = array();
				while( $row = $db->fetch_array($result) )
				{
					$file_ids[] = $row['file_id'];
				}
			}
			
			if( count($file_ids) > 0 )
			{
				$filename_ary = array();
				
				switch( DATABASE )
				{
					case 'postgre':
						$sql = "SELECT jf.file_id, jf.file_physical_name 
							FROM " . JOINED_FILES_TABLE . " AS jf 
							WHERE jf.file_id IN(
								SELECT lf.file_id 
								FROM " . LOG_FILES_TABLE . " AS lf 
								WHERE lf.file_id IN(" . implode(', ', $file_ids) . ") 
								GROUP BY lf.file_id 
								HAVING COUNT(lf.file_id) = 1
							)";
						break;
					
					default:
						$sql = "SELECT lf.file_id, jf.file_physical_name 
							FROM " . LOG_FILES_TABLE . " AS lf, " . JOINED_FILES_TABLE . " AS jf 
							WHERE lf.file_id IN(" . implode(', ', $file_ids) . ") 
								AND lf.file_id = jf.file_id 
							GROUP BY lf.file_id 
							HAVING COUNT(lf.file_id) = 1";
						break;
				}
				
				if( !($result = $db->query($sql)) )
				{
					trigger_error('Impossible d\'obtenir la liste des fichiers � supprimer', ERROR);
				}
				
				if( $row = $db->fetch_array($result) )
				{
					$ids = array();
					
					do
					{
						$ids[]          = $row['file_id'];
						$filename_ary[] = $row['file_physical_name'];
					}
					while( $row = $db->fetch_array($result) );
					
					$sql = "DELETE FROM " . JOINED_FILES_TABLE . " 
						WHERE file_id IN(" . implode(', ', $ids) . ")";
					if( !$db->query($sql) )
					{
						trigger_error('Impossible de supprimer les entr�es inutiles de la table des fichiers joints', ERROR);
					}
				}
				
				$sql = "DELETE FROM " . LOG_FILES_TABLE . " 
					WHERE log_id IN(" . implode(', ', $log_ids) . ") 
						AND file_id IN(" . implode(', ', $file_ids) . ")";
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de supprimer les entr�es de la table log_files', ERROR);
				}
				
				//
				// Suppression physique des fichiers joints devenus inutiles
				//
				foreach( $filename_ary AS $filename )
				{
					if( $this->use_ftp )
					{
						if( !@ftp_delete($this->connect_id, $filename) )
						{
							trigger_error('Ftp_error_del', ERROR);
						}
					}
					else
					{
						$this->remove_file(wa_realpath($this->upload_path . $filename));
					}
				}
				
				return count($filename_ary);
			}// end count file_id_ary
		}// end count log_id_ary
		
		return false;
	}
	
	/**
	 * Attach::remove_file()
	 * 
	 * Suppression d'un fichier du serveur
	 * 
	 * @param string $filename    Nom du fichier sur le serveur
	 * 
	 * @return void
	 */
	function remove_file($filename)
	{
		@unlink($filename);
	}
	
	/**
	 * Attach::send_file()
	 * 
	 * Fonction d'envois des ent�tes n�cessaires au t�l�chargement et 
	 * des donn�es du fichier � t�l�charger
	 * 
	 * @param string $filename     Nom r�el du fichier
	 * @param string $mime_type    Mime type du fichier
	 * @param string $filedata     Contenu du fichier
	 * 
	 * @return void
	 */
	function send_file($filename, $mime_type, $data)
	{
		//
		// Si aucun type de m�dia n'est indiqu�, on utilisera par d�faut 
		// le type application/octet-stream (application/octetstream pour IE et Opera).
		// Si le type application/octet-stream	ou application/octetstream est indiqu�, on fait 
		// �ventuellement le changement si le type n'est pas bon pour l'agent utilisateur.
		// Si on a � faire � Opera, on utilise application/octetstream car toute autre type peut poser 
		// d'�ventuels probl�mes.
		//
		if( empty($mime_type) || eregi('application/octet-?stream', $mime_type) || WA_USER_BROWSER == 'opera' )
		{
			if( WA_USER_BROWSER == 'msie' || WA_USER_BROWSER == 'opera' )
			{
				$mime_type = 'application/octetstream';
			}
			else
			{
				$mime_type = 'application/octet-stream';
			}
		}
		
		//
		// D�sactivation de la compression de sortie de php au cas o� 
		// et envoi des en-t�tes appropri�s au client.
		//
		@ini_set('zlib.output_compression', 'Off');
		header('Content-Length: ' . strlen($data));
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Type: ' . $mime_type . '; name="' . $filename . '"');
		
		echo $data;
		exit;
	}
	
	/**
	 * Attach::quit()
	 * 
	 * Fermeture de la connexion au serveur ftp
	 * 
	 * @return void
	 */
	function quit()
	{
		if( $this->use_ftp )
		{
			$quit = ( version_compare(phpversion(), '4.2.0', '>=') == true ) ? 'ftp_close' : 'ftp_quit';
			
			@$quit($this->connect_id);
		}
	}
}

}
?>