<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aur�lien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('IN_INSTALL') && !defined('IN_UPGRADE') )
{
	exit('<b>No hacking</b>');
}

define('IN_NEWSLETTER', true);
define('WA_ROOTDIR',    '..');
define('WAMAILER_DIR',  WA_ROOTDIR . '/includes/wamailer');
define('SCHEMAS_DIR',   WA_ROOTDIR . '/setup/schemas');

function message($message, $l_title = null)
{
	global $lang, $output;
	
	if( !empty($lang['Message'][$message]) )
	{
		$message = $lang['Message'][$message];
	}
	
	$output->send_headers();
	
	$output->set_filenames( array(
		'body' => 'result.tpl'
	));
	
	if( is_null($l_title) )
	{
		if( defined('IN_INSTALL') )
		{
			$l_title = ( defined('NL_INSTALLED') ) ? $lang['Title']['reinstall'] : $lang['Title']['install'];
		}
		else
		{
			$l_title = $lang['Title']['upgrade'];
		}
	}
	
	$output->assign_vars(array(
		'PAGE_TITLE'   => $l_title,
		'CONTENT_LANG' => $lang['CONTENT_LANG'],
		'CONTENT_DIR'  => $lang['CONTENT_DIR'],
		'NEW_VERSION'  => WANEWSLETTER_VERSION,
		'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : '',
		'L_TITLE'      => $lang['Title']['info'],
		'MSG_RESULT'   => nl2br($message)
	));
	
	$output->pparse('body');
	exit;
}

function sql_error()
{
	global $db, $lang, $output;
	
	$output->send_headers();
	
	$output->set_filenames( array(
		'body' => 'result.tpl'
	));
	
	if( defined('IN_INSTALL') )
	{
		$l_title = ( defined('NL_INSTALLED') ) ? $lang['Title']['reinstall'] : $lang['Title']['install'];
		$message = $lang['Error_in_install'];
	}
	else
	{
		$l_title = $lang['Title']['upgrade'];
		$message = $lang['Error_in_upgrade'];
	}
	
	$output->assign_vars(array(
		'PAGE_TITLE'   => $l_title,
		'CONTENT_LANG' => $lang['CONTENT_LANG'],
		'CONTENT_DIR'  => $lang['CONTENT_DIR'],
		'NEW_VERSION'  => WANEWSLETTER_VERSION,
		'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : '',
		'L_TITLE'      => $lang['Title']['info'],
		'MSG_RESULT'   => nl2br(sprintf($message, $db->error, $db->lastQuery))
	));
	
	$output->pparse('body');
	exit;
}

function exec_queries(&$sql_ary, $return_error = false)
{
	global $db;
	
	if( !is_array($sql_ary) )
	{
		$sql_ary = array($sql_ary);
	}
	
	foreach( $sql_ary as $query )
	{
		$result = $db->query($query);
		
		if( !$result && $return_error )
		{
			sql_error();
		}
	}
	
	$sql_ary = array();
}

error_reporting(E_ALL);

require WA_ROOTDIR . '/includes/compat.inc.php';
require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/constantes.php';
require WA_ROOTDIR . '/includes/template.php';
require WA_ROOTDIR . '/includes/class.output.php';

$output = new output(WA_ROOTDIR . '/templates/');


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
		strip_magic_quotes_gpc($_REQUEST);
	}
}

$default_lang   = 'francais';
$supported_lang = array(
	'fr' => 'francais',
	'en' => 'english'
);

$supported_db = array(
	'mysql' => array(
		'Name'         => 'MySQL &#8805; 4.1, 5.x',
		'prefixe_file' => 'mysql',
		'extension'    => (extension_loaded('mysql') || extension_loaded('mysqli'))
	),
	'postgres' => array(
		'Name'         => 'PostgreSQL &#8805; 7.2, 8.x',
		'prefixe_file' => 'postgres',
		'extension'    => extension_loaded('pgsql')
	),
	'sqlite' => array(
		'Name'         => 'SQLite &#8805; 2.8, 3.x',
		'prefixe_file' => 'sqlite',
		'extension'    => (class_exists('SQLite3') || extension_loaded('sqlite') || (extension_loaded('pdo') && extension_loaded('pdo_sqlite')))
	)
);

$sql_drop_table = array(
	'DROP TABLE wa_abo_liste',
	'DROP TABLE wa_abonnes',
	'DROP TABLE wa_admin',
	'DROP TABLE wa_auth_admin',
	'DROP TABLE wa_ban_list',
	'DROP TABLE wa_config',
	'DROP TABLE wa_joined_files',
	'DROP TABLE wa_forbidden_ext',
	'DROP TABLE wa_liste',
	'DROP TABLE wa_log',
	'DROP TABLE wa_log_files',
	'DROP TABLE wa_session'
);

$sql_drop_index = array(
	'DROP INDEX abo_status_idx',
	'DROP INDEX admin_id_idx',
	'DROP INDEX config_name_idx',
	'DROP INDEX liste_id_idx',
	'DROP INDEX log_status_idx'
);

$sql_drop_sequence = array(
	'DROP SEQUENCE wa_abonnes_id_seq',
	'DROP SEQUENCE wa_admin_id_seq',
	'DROP SEQUENCE wa_ban_id_seq',
	'DROP SEQUENCE wa_config_id_seq',
	'DROP SEQUENCE wa_forbidden_ext_id_seq',
	'DROP SEQUENCE wa_joined_files_id_seq',
	'DROP SEQUENCE wa_liste_id_seq',
	'DROP SEQUENCE wa_log_id_seq'
);

$sql_drop_generator = array(
	'DROP GENERATOR wa_abonnes_gen',
	'DROP GENERATOR wa_admin_gen',
	'DROP GENERATOR wa_ban_list_gen',
	'DROP GENERATOR wa_forbidden_ext_gen',
	'DROP GENERATOR wa_joined_files_gen',
	'DROP GENERATOR wa_liste_gen',
	'DROP GENERATOR wa_log_gen'
);

$sql_drop_trigger = array(
	'DROP TRIGGER wa_abonnes_gen_t',
	'DROP TRIGGER wa_admin_gen_t',
	'DROP TRIGGER wa_ban_list_gen_t',
	'DROP TRIGGER wa_forbidden_ext_gen_t',
	'DROP TRIGGER wa_joined_files_gen_t',
	'DROP TRIGGER wa_liste_gen_t',
	'DROP TRIGGER wa_log_gen_t'
);

$dsn = '';
$lang    = $datetime = $msg_error = $_php_errors = array();
$error   = false;
$prefixe = ( !empty($_POST['prefixe']) ) ? trim($_POST['prefixe']) : 'wa_';
$infos   = array('engine' => 'mysql', 'host' => null, 'user' => null, 'pass' => null, 'dbname' => null);
$language = $default_lang;

$dbtype = $dbhost = $dbuser = $dbpassword = $dbname = ''; // Compatibilit� avec wanewsletter < 2.3-beta2

if( file_exists(WA_ROOTDIR . '/includes/config.inc.php') )
{
	@include WA_ROOTDIR . '/includes/config.inc.php';
}

if( server_info('HTTP_ACCEPT_LANGUAGE') != '' )
{
	$accept_lang_ary = array_map('trim', explode(',', server_info('HTTP_ACCEPT_LANGUAGE')));
	
	foreach( $accept_lang_ary as $accept_lang )
	{
		$accept_lang = strtolower(substr($accept_lang, 0, 2));
		
		if( isset($supported_lang[$accept_lang]) && file_exists(WA_ROOTDIR . '/language/lang_' . $supported_lang[$accept_lang] . '.php') )
		{
			$language = $supported_lang[$accept_lang];
			break;
		}
	}
}

require WA_ROOTDIR . '/language/lang_' . $language . '.php';
require WA_ROOTDIR . '/includes/wadb_init.php';
require WA_ROOTDIR . '/includes/sql/sqlparser.php';

//
// V�rification de la version de PHP disponible.
//
if( !version_compare(PHP_VERSION, WA_PHP_VERSION_REQUIRED, '>=') )
{
	message(sprintf($lang['PHP_version_error'], PHP_VERSION, WA_PHP_VERSION_REQUIRED));
}

if( !empty($dsn) )
{
	list($infos) = parseDSN($dsn);
}

//
// Compatibilit� avec les version ant�rieures � 2.3-beta2 (d�but utilisation des DSN)
//
else if( !empty($dbtype) )
{
	$infos['engine'] = !empty($dbtype) ? $dbtype : $infos['engine'];
	$infos['host']   = !empty($dbhost) ? $dbhost : null;
	$infos['user']   = !empty($dbuser) ? $dbuser : null;
	$infos['pass']   = !empty($dbpassword) ? $dbpassword : null;
	$infos['dbname'] = !empty($dbname) ? $dbname : null;
}

foreach( array('engine', 'host', 'user', 'pass', 'dbname') as $varname )
{
	$infos[$varname] = ( !empty($_POST[$varname]) ) ? trim($_POST[$varname]) : @$infos[$varname];
}

// R�cup�ration du port, si associ� avec le nom d'h�te
if( strpos($infos['host'], ':') )
{
	$tmp = explode(':', $infos['host']);
	$infos['host'] = $tmp[0];
	$infos['port'] = $tmp[1];
}

foreach( array('start', 'confirm', 'sendfile') as $varname )
{
	${$varname} = ( isset($_POST[$varname]) ) ? true : false;
}

if( !defined('IN_INSTALL') && empty($infos['dbname']) )
{
	message($lang['Not_installed']);
}
else if( $infos['engine'] == 'mssql' )
{
	message($lang['mssql_support_end']);
}
else if( $infos['engine'] == 'postgre' )
{
	$infos['engine'] = 'postgres';
}
else if( $infos['engine'] == 'mysql4' || $infos['engine'] == 'mysqli' )
{
	$infos['engine'] = 'mysql';
}
else if( $infos['engine'] == 'sqlite_pdo' || $infos['engine'] == 'sqlite3' )
{
	$infos['engine'] = 'sqlite';
}

foreach( $supported_db as $name => $data )
{
	if( $data['extension'] === false )
	{
		unset($supported_db[$name]);
	}
}

if( count($supported_db) == 0 )
{
	message(sprintf($lang['No_db_support'], WANEWSLETTER_VERSION));
}

if( !isset($supported_db[$infos['engine']]) && ( defined('NL_INSTALLED') || defined('IN_UPGRADE') ) )
{
	plain_error($lang['DB_type_undefined']);
}

if( $infos['engine'] == 'sqlite' )
{
	$infos['dbname'] = wa_realpath(WA_ROOTDIR . '/includes/sql') . '/wanewsletter.sqlite';
}

if( !empty($infos['dbname']) )
{
	$dsn = createDSN($infos);
}

$config_file  = '<' . "?php\n";
$config_file .= "\n";
$config_file .= "//\n";
$config_file .= "// Param�tres d'acc�s � la base de donn�es\n";
$config_file .= "// Ne pas modifier ce fichier ! (Do not edit this file)\n";
$config_file .= "//\n";
$config_file .= "define('NL_INSTALLED', true);\n";
$config_file .= "\n";
$config_file .= "\$dsn = '$dsn';\n";
$config_file .= "\$prefixe = '$prefixe';\n";
$config_file .= "\n";

//
// Envoi du fichier au client si demand�
//
if( $sendfile == true )
{
	require WA_ROOTDIR . '/includes/class.attach.php';
	
	Attach::send_file('config.inc.php', 'text/plain', $config_file);
}

?>