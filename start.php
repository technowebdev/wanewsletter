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

// @link http://bugs.php.net/bug.php?id=31440
if( isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']) ) {
	exit('GLOBALS overwrite attempt detected');
}

error_reporting(E_ALL);

$starttime = array_sum(explode(' ', microtime()));

//
// Intialisation des variables pour �viter toute injection malveillante de code 
//
$simple_header = $error = false;
$nl_config     = $lang = $datetime = $admindata = $msg_error = $other_tags = $_php_errors = array();
$output = null;
$dsn = $prefixe = $php_errormsg = '';

@include WA_ROOTDIR . '/includes/config.inc.php';

if( !defined('NL_INSTALLED') )
{
	if( !empty($_SERVER['SERVER_SOFTWARE']) && preg_match("#Microsoft|WebSTAR|Xitami#i", $_SERVER['SERVER_SOFTWARE']) )
	{
		$header_location = 'Refresh: 0; URL=';
	}
	else
	{
		$header_location = 'Location: ';
	}
	
	$path = ( file_exists('setup/install.php') ) ? 'setup/install.php' : '../setup/install.php';
	
	header($header_location . $path);
	exit;
}

require WA_ROOTDIR . '/includes/compat.inc.php';
require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/constantes.php';
require WA_ROOTDIR . '/includes/wadb_init.php';

//
// Appel du gestionnaire d'erreur 
//
if( defined('IN_COMMANDLINE') )
{
	//
	// Compatibilit� avec PHP en CGI
	//
	if( php_sapi_name() != 'cli' )
	{
		define('STDIN',  fopen('php://stdin', 'r'));
		define('STDOUT', fopen('php://stdout', 'w'));
		define('STDERR', fopen('php://stderr', 'w'));
	}
	
	set_error_handler('wan_cli_handler');
}
else
{
	require WA_ROOTDIR . '/includes/template.php';
	require WA_ROOTDIR . '/includes/class.output.php';
	
	set_error_handler('wan_web_handler');
}

//
// Les guillemets magiques ont �t� supprim�s dans PHP 5.4.0
//
if( version_compare(PHP_VERSION, '5.4.0', '<') )
{
	//
	// D�sactivation de magic_quotes_runtime +
	// magic_quotes_gpc et retrait �ventuel des backslashes
	//
	@ini_set('magic_quotes_runtime', 0);

	if( get_magic_quotes_gpc() )
	{
		strip_magic_quotes_gpc($_GET);
		strip_magic_quotes_gpc($_POST);
		strip_magic_quotes_gpc($_COOKIE);
		strip_magic_quotes_gpc($_FILES, true);
		strip_magic_quotes_gpc($_REQUEST);
	}
}

//
// Intialisation de la connexion � la base de donn�es 
//
$db = WaDatabase($dsn);

if( !$db->isConnected() )
{
	trigger_error('<b>Impossible de se connecter � la base de donn�es</b>', CRITICAL_ERROR);
}

//
// On r�cup�re la configuration du script 
//
$nl_config = wa_get_config();

//
// "Constantes" de classe dans le scope global
// Pas plus haut car on a besoin d'une instance de Wadb_* et WadbResult_*
//
define('SQL_INSERT', $db->SQL_INSERT);
define('SQL_UPDATE', $db->SQL_UPDATE);
define('SQL_DELETE', $db->SQL_DELETE);

//
// Purge 'automatique' des listes (comptes non activ�s au-del� du temps limite)
//
if( !(time() % 10) || !defined('IN_ADMIN') )
{
	purge_liste();
}

//
// Nom du dossier des fichiers temporaires du script
// Le nom ne doit contenir / ni au d�but, ni � la fin
//
$tmp_name = 'tmp';

define('WA_TMPDIR',    WA_ROOTDIR . '/' . $tmp_name);
define('WAMAILER_DIR', WA_ROOTDIR . '/includes/wamailer');
define('WA_LOCKFILE',  WA_TMPDIR . '/liste-%d.lock');

if( !is_writable(WA_TMPDIR) )
{
	load_settings();
	$output->displayMessage(sprintf($lang['Message']['Dir_not_writable'],
		wan_htmlspecialchars(wa_realpath(WA_TMPDIR))));
}

