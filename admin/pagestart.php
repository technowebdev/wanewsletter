<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aur�lien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('IN_NEWSLETTER') )
{
	exit('<b>No hacking</b>');
}

define('IN_ADMIN',   true);
define('WA_ROOTDIR', '..');

$secure = true;

require WA_ROOTDIR . '/start.php';
require WA_ROOTDIR . '/includes/class.sessions.php';
require WA_ROOTDIR . '/includes/class.auth.php';

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
		
		$sql = "UPDATE " . SESSIONS_TABLE . "
			SET session_liste = 0 
			WHERE session_id = '" . $session->session_id . "' 
				AND admin_id = " . $admindata['admin_id'];
		if( !$db->query($sql) )
		{
			trigger_error('Impossible de mettre � jour le session_liste', ERROR);
		}
	}
	
	if( $secure && strtoupper(server_info('REQUEST_METHOD')) == 'POST' && $session->new_session )
	{
		$output->displayMessage('Invalid_session');
	}
}
