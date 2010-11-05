<?php
/**
 * Copyright (c) 2002-2010 Aur�lien Maille
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

//
// Mode de d�bugguage du script 
// 
// 3 - Toutes les erreurs sont affich�es � l'�cran
// 2 - Toutes les erreurs provenant de variables/fonctions/autres non pr�c�d�s d'un @ sont affich�es � l'�cran 
// 1 - Seules les erreurs en rapport avec la base de donn�es sont affich�es (le script donne des d�tails sur l'erreur)
// 0 - Le script affiche simplement un message d'erreur lors de probl�mes SQL, sans donner plus de d�tails 
//
define('DEBUG_MODE', 3);

//
// Pour visualiser le temps d'ex�cution du script et le nombre de requ�tes effectu�es
//
define('DEV_INFOS', TRUE);
//define('DEV_INFOS', FALSE);

//
// Active/D�sactive l'affichage des erreurs proprement dans un bloc html en bas de page
//
define('DISPLAY_ERRORS_IN_BLOCK', TRUE);

//
// Active/D�sactive le passage automatique � l'UTF-8 au moment de l'envoi en pr�sence de 
// caract�res invalides provenant de Windows-1252 dans les newsletters.
//
// Si cette constante est plac�e � TRUE, les caract�res en cause subiront une transformation
// vers un caract�re simple ou compos� graphiquement proche (voir la fonction purge_latin1()
// dans le fichier includes/functions.php).
//
define('TRANSLITE_INVALID_CHARS', FALSE);

//
// Format des exportations d'archive
//
define('EXPORT_FORMAT', 'Tar'); // Tar ou Zip (Attention, respecter la casse)

//
// Prise en compte de l'authentification HTTP pour la connexion automatique
//
define('ENABLE_HTTP_AUTHENTICATION', TRUE);


//
// Il est recommand� de ne rien modifier au-del� de cette ligne
//

//
// Codes des messages d'erreur et d'information 
//
define('CRITICAL_ERROR', E_USER_ERROR);
define('ERROR',          E_USER_WARNING);

if( !defined('E_STRICT') ) // Compatibilit� PHP5
{
	define('E_STRICT', 2048);
}

if( !defined('E_DEPRECATED') ) // Compatibilit� PHP5.3
{
	define('E_DEPRECATED', 8192);
}

if( !defined('DATE_RFC1123') ) // Compatibilit� PHP 5.1.1
{
	define('DATE_RFC1123', 'D, d M Y H:i:s T');
}

//
// Formats d'emails 
//
define('FORMAT_TEXTE',    1);
define('FORMAT_HTML',     2);
define('FORMAT_MULTIPLE', 3);

//
// Statut des newsletter 
//
define('STATUS_WRITING', 0);
define('STATUS_STANDBY', 1);
define('STATUS_SENDED',  2);
define('STATUS_MODEL',   3);

//
// Statut des abonn�s 
//
define('ABO_ACTIF',   1);
define('ABO_INACTIF', 0);

define('SUBSCRIBE_CONFIRMED',     1);
define('SUBSCRIBE_NOT_CONFIRMED', 0);

//
// Niveau des utilisateurs, ne pas modifier !! 
//
define('ADMIN', 2);
define('USER',  1);

//
// divers 
//
define('SUBSCRIBE_NOTIFY_YES', 1);
define('SUBSCRIBE_NOTIFY_NO',  0);
define('UNSUBSCRIBE_NOTIFY_YES', 1);
define('UNSUBSCRIBE_NOTIFY_NO',  0);

define('MAX_IMPORT', 5000);

define('ENGINE_BCC',  1);
define('ENGINE_UNIQ', 2);

define('CONFIRM_ALWAYS', 2);
define('CONFIRM_ONCE',   1);
define('CONFIRM_NONE',   0);

if( !defined('UPLOAD_ERR_NO_TMP_DIR') ) // Introduite en PHP 4.3.10 et 5.0.3
{
	define('UPLOAD_ERR_NO_TMP_DIR', 6);
}

if( !defined('UPLOAD_ERR_CANT_WRITE') ) // Introduite en PHP 5.1.0
{
	define('UPLOAD_ERR_CANT_WRITE', 7);
}

//
// Si nous avons un acc�s restreint � cause de open_basedir, certains fichiers upload�s 
// devront �tre d�plac�s vers le dossier des fichiers temporaires du script pour �tre 
// accessible en lecture
//
$open_basedir = config_value('open_basedir');
if( !empty($open_basedir) )
{
	define('OPEN_BASEDIR_RESTRICTION', TRUE);
}
else
{
	define('OPEN_BASEDIR_RESTRICTION', FALSE);
}

//
// On v�rifie si l'upload est autoris� sur le serveur
//
if( config_status('file_uploads') )
{
	function get_integer_byte_value($size)
	{
		if( preg_match('/^([0-9]+)([KMG])$/i', $size, $match) )
		{
			switch( strtoupper($match[2]) )
			{
				case 'K':
					$size = ($match[1] * 1024);
					break;
				
				case 'M':
					$size = ($match[1] * 1024 * 1024);
					break;
				
				case 'G': // Since php 5.1.0
					$size = ($match[1] * 1024 * 1024 * 1024);
					break;
			}
		}
		else
		{
			$size = intval($size);
		}
		
		return $size;
	}
	
	if( !($filesize = config_value('upload_max_filesize')) )
	{
        $filesize = '2M'; // 2 M�ga-Octets
    }
	$upload_max_size = get_integer_byte_value($filesize);
	
    if( $postsize = config_value('post_max_size') )
	{
        $postsize = get_integer_byte_value($postsize);
        if( $postsize < $upload_max_size )
		{
            $upload_max_size = $postsize;
        }
    }
	
	define('FILE_UPLOADS_ON', TRUE);
	define('MAX_FILE_SIZE',   $upload_max_size);
}
else
{
	define('FILE_UPLOADS_ON', FALSE);
	define('MAX_FILE_SIZE',   0);
}

//
// Infos sur l'utilisateur 
//
$user_agent = server_info('HTTP_USER_AGENT');

if( $user_agent != '' )
{
	if( stristr($user_agent, 'win') )
	{
		define('WA_USER_OS', 'win');
	}
	else if( stristr($user_agent, 'mac') )
	{
		define('WA_USER_OS', 'mac');
	}
	else if( stristr($user_agent, 'linux') )
	{
		define('WA_USER_OS', 'linux');
	}
	else
	{
		define('WA_USER_OS', 'other');
	}
	
	if( stristr($user_agent, 'opera') )
	{
		define('WA_USER_BROWSER', 'opera');
	}
	else if( stristr($user_agent, 'msie') )
	{
		define('WA_USER_BROWSER', 'msie');
	}
	else if( stristr($user_agent, 'konqueror') )
	{
		define('WA_USER_BROWSER', 'konqueror');
	}
	else if( stristr($user_agent, 'mozilla') )
	{
		define('WA_USER_BROWSER', 'mozilla');
	}
	else
	{
		define('WA_USER_BROWSER', 'other');
	}
}
else
{
	define('WA_USER_OS',      'other');
	define('WA_USER_BROWSER', 'other');
}

if( defined('WA_VERSION') )
{
	//
	// Cha�ne utilis�e pour d�finir l'ent�te X-Mailer des emails envoy�s par Wanewsletter
	//
	define('WA_X_MAILER', sprintf('Wanewsletter/%s', WA_VERSION));
}

