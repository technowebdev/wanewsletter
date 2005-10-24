<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
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
define('DEBUG_MODE', 2);

//
// Pour visualiser le temps d'ex�cution du script et le nombre de requ�tes effectu�es
//
define('DEV_INFOS', TRUE);
//define('DEV_INFOS', FALSE);

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

//
// Codes des messages d'erreur et d'information 
//
define('CRITICAL_ERROR', E_USER_WARNING);
define('ERROR',          E_USER_ERROR);
define('MESSAGE',        E_USER_NOTICE);

if( !defined('E_STRICT') ) {// Compatibilit� PHP5
	define('E_STRICT', 2048);
}

//
// Codes transactions pour les DB qui les supportent 
//
define('START_TRC', 1);
define('END_TRC',   2);

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
define('STATUS_HANDLE',  3);

//
// Statut des abonn�s 
//
define('ABO_ACTIF',   1);
define('ABO_INACTIF', 0);

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

define('MAX_IMPORT', 2500);

define('ENGINE_BCC',  1);
define('ENGINE_UNIQ', 2);

//
// Si nous un acc�s restreint � cause de open_basedir, certains fichiers upload�s 
// devront �tre d�plac�s vers le dossier des fichiers temporaires du script pour �tre 
// accessible en lecture
//
$open_basedir = config_status('open_basedir');
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
	define('FILE_UPLOADS_ON', TRUE);
}
else
{
	define('FILE_UPLOADS_ON', FALSE);
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

?>