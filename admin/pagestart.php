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

if( !defined('IN_NEWSLETTER') )
{
	exit('<b>No hacking</b>');
}

define('IN_ADMIN', true);

$secure = TRUE;
$waroot = '../';
require $waroot . 'start.php';

include $waroot . 'includes/class.sessions.php';
include $waroot . 'includes/class.auth.php';

$liste = ( !empty($_REQUEST['liste']) ) ? intval($_REQUEST['liste']) : 0;

//
//// Start session and load settings 
//
$session = new Session();

$admindata = $session->check($liste);
load_settings($admindata);
//
//// End 
//

if( !defined('IN_LOGIN') )
{
	if( !$admindata )
	{
		$redirect  = '?redirect=' . basename(server_info('PHP_SELF'));
		$redirect .= ( server_info('QUERY_STRING') != '' ) ? rawurlencode('?' . server_info('QUERY_STRING')) : '';
		
		Location('login.php' . $redirect);
	}
	
	$auth = new Auth();
	
	//
	// Si la liste en session n'existe pas, on met � jour la session
	//
	if( !isset($auth->listdata[$admindata['session_liste']]) )
	{
		$admindata['session_liste'] = 0;
		
		$sql = "UPDATE " . SESSIONS_TABLE . " SET session_liste = 0 
			WHERE session_id = '" . $session->session_id . "' 
				AND admin_id = " . $admindata['admin_id'];
		if( !$db->query($sql) )
		{
			trigger_error('Impossible de mettre � jour le session_liste', ERROR);
		}
	}
	
	if( $secure && strtoupper(server_info('REQUEST_METHOD')) == 'POST' )
	{
		$sessid = ( !empty($_POST['sessid']) ) ? trim($_POST['sessid']) : '';
		
		if( $session->new_session || ( $sessid != $session->session_id ) )
		{
			trigger_error('Invalid_session', MESSAGE);
		}
	}
}

//
// Si nous avons un acc�s restreint � cause d'open_basedir sur le serveur, 
// nous devrons utiliser le dossier des fichiers temporaires du script 
//
$tmp_name = preg_replace('/\/?(.*?)\/?/', '\\1', $tmp_name);

if( OPEN_BASEDIR_RESTRICTION && !is_writable($waroot . $tmp_name) )
{
	trigger_error('tmp_dir_not_writable', MESSAGE);
}

define('WA_TMP_PATH', $waroot . $tmp_name, true);

?>