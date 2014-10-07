<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aur�lien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_NEWSLETTER', true);
define('IN_INSTALL', true);
define('WA_ROOTDIR', '.');

require WA_ROOTDIR . '/includes/common.inc.php';

function message($message)
{
	global $lang, $output;
	
	if( !empty($lang['Message'][$message]) )
	{
		$message = $lang['Message'][$message];
	}
	
	$output->assign_block_vars('result', array(
		'L_TITLE'    => $lang['Result_install'],
		'MSG_RESULT' => nl2br($message)
	));
	
	$output->pparse('body');
	$output->page_footer();
	exit;
}

$prefixe = ( !empty($_POST['prefixe']) ) ? trim($_POST['prefixe']) : 'wa_';
$infos   = array('engine' => 'mysql', 'host' => null, 'user' => null, 'pass' => null, 'dbname' => null);

if( !empty($dsn) )
{
	list($infos) = parseDSN($dsn);
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

if( !isset($supported_db[$infos['engine']]) && defined('NL_INSTALLED') )
{
	message($lang['DB_type_undefined']);
}

if( $infos['engine'] == 'sqlite' )
{
	$infos['host'] = null;
	
	if( !defined('NL_INSTALLED') )
	{
		$infos['dbname'] = wa_realpath(WA_ROOTDIR . '/data/db') . '/wanewsletter.sqlite';
	}
}

if( !empty($infos['dbname']) )
{
	$dsn = createDSN($infos);
}

$vararray = array(
	'language', 'prev_language', 'admin_login', 'admin_email', 'admin_pass', 
	'confirm_pass', 'urlsite', 'urlscript'
);
foreach( $vararray as $varname )
{
	${$varname} = ( !empty($_POST[$varname]) ) ? trim($_POST[$varname]) : '';
}

//
// Envoi du fichier au client si demand�
//
// Attention, $config_file est aussi utilis� � la fin de l'installation pour
// pour cr�er le fichier de configuration.
//
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

if( isset($_POST['sendfile']) )
{
	require WA_ROOTDIR . '/includes/class.attach.php';
	
	Attach::send_file('config.inc.php', 'text/plain', $config_file);
}

$supported_lang = array(
	'fr' => 'francais',
	'en' => 'english'
);

$language = ( $language != '' ) ? $language : $supported_lang[$lang['CONTENT_LANG']];

$start = isset($_POST['start']);

if( $start && $language != $prev_language )
{
	$start = false;
}

$nl_config['language'] = $language;

if( defined('NL_INSTALLED') )
{
	$db = WaDatabase($dsn);
	
	if( !$db->isConnected() )
	{
		plain_error(sprintf($lang['Connect_db_error'], $db->error));
	}
	
	$nl_config = wa_get_config();
	$urlsite    = $nl_config['urlsite'];
	$urlscript  = $nl_config['path'];
	$language   = $nl_config['language'];
}

load_settings();

$output->set_filenames( array(
	'body' => 'install.tpl'
));

$output->send_headers();

$output->assign_vars( array(
	'PAGE_TITLE'   => ( defined('NL_INSTALLED') ) ? $lang['Title']['reinstall'] : $lang['Title']['install'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR'],
	'CHARSET'      => $lang['CHARSET']
));

if( $start )
{
	require WA_ROOTDIR . '/includes/functions.validate.php';
	require WAMAILER_DIR . '/class.mailer.php';
	
	if( defined('NL_INSTALLED') )
	{
		$login = false;
		
		$sql = "SELECT admin_email, admin_pwd, admin_level 
			FROM " . ADMIN_TABLE . " 
			WHERE LOWER(admin_login) = '" . $db->escape(strtolower($login)) . "'
				AND admin_level = " . ADMIN;
		$result = $db->query($sql);
		
		if( $row = $result->fetch() )
		{
			$hasher = new PasswordHash();
			
			// Ugly old md5 hash prior Wanewsletter 2.4-beta2
			if( $row['admin_pwd'][0] != '$' )
			{
				if( $row['admin_pwd'] === md5($passwd) )
				{
					$login = true;
				}
			}
			// New password hash using phpass
			else if( $hasher->check($passwd, $row['admin_pwd']) )
			{
				$login = true;
			}
			
			if( $login )
			{
				$admin_email  = $row['admin_email'];
				$confirm_pass = $admin_pass;
			}
		}
		
		if( !$login )
		{
			$error = true;
			$msg_error[] = $lang['Message']['Error_login'];
		}
	}
	else
	{
		if( $infos['engine'] == 'sqlite' )
		{
			if( is_writable(dirname($infos['dbname'])) )
			{
				$db = WaDatabase($dsn);
			}
			else
			{
				$error = true;
				$msg_error[] = $lang['sqldir_perms_problem'];
			}
		}
		else if( !empty($dsn) )
		{
			$db = WaDatabase($dsn);
		}
		else
		{
			$error = true;
			$msg_error[] = sprintf($lang['Connect_db_error'], 'Invalid DB name');
		}
		
		if( !$error && !$db->isConnected() )
		{
			$error = true;
			$msg_error[] = sprintf($lang['Connect_db_error'], $db->error);
		}
	}
	
	$sql_create = WA_ROOTDIR . '/data/schemas/' . $infos['engine'] . '_tables.sql';
	$sql_data   = WA_ROOTDIR . '/data/schemas/data.sql';
	
	if( !is_readable($sql_create) || !is_readable($sql_data) )
	{
		$error = true;
		$msg_error[] = $lang['Message']['sql_file_not_readable'];
	}
	
	if( !$error )
	{
		if( $infos['dbname'] == '' || $prefixe == '' || $admin_login == '' )
		{
			$error = true;
			$msg_error[] = $lang['Message']['fields_empty'];
		}
		
		if( !validate_pass($admin_pass) )
		{
			$error = true;
			$msg_error[] = $lang['Message']['Alphanum_pass'];
		}
		else if( $admin_pass !== $confirm_pass )
		{
			$error = true;
			$msg_error[] = $lang['Message']['Bad_confirm_pass'];
		}
		
		if( !Mailer::validate_email($admin_email) )
		{
			$error = true;
			$msg_error[] = $lang['Message']['Invalid_email'];
		}
		
		$urlsite = rtrim($urlsite, '/');
		
		if( $urlscript != '/' )
		{
			$urlscript = '/' . trim($urlscript, '/') . '/';
		}
	}
	
	if( !$error )
	{
		require WA_ROOTDIR . '/includes/sql/sqlparser.php';
		
		//
		// On allonge le temps maximum d'execution du script. 
		//
		@set_time_limit(300);
		
		if( defined('NL_INSTALLED') )
		{
			$sql_drop = array();
			
			foreach( $sql_schemas as $tablename => $schema )
			{
				if( $db->engine == 'postgres' && !empty($schema['sequence']) )
				{
					foreach( $schema['sequence'] as $sequence )
					{
						$sql_drop[] = sprintf("DROP SEQUENCE IF EXISTS %s",
							str_replace('wa_', $prefixe, $sequence)
						);
					}
				}
				
				if( !empty($schema['index']) )
				{
					foreach( $schema['index'] as $index )
					{
						$sql_drop[] = sprintf("DROP INDEX IF EXISTS %s",
							str_replace('wa_', $prefixe, $index)
						);
					}
				}
				
				$sql_drop[] = sprintf("DROP TABLE IF EXISTS %s",
					str_replace('wa_', $prefixe, $tablename)
				);
			}
			
			exec_queries($sql_drop);
		}
		
		//
		// Cr�ation des tables du script 
		//
		$sql_create = parseSQL(file_get_contents($sql_create), $prefixe);
		exec_queries($sql_create);
		
		//
		// Insertion des donn�es de base 
		//
		$sql_data = parseSQL(file_get_contents($sql_data), $prefixe);
		
		$hasher = new PasswordHash();
		
		$sql_data[] = "UPDATE " . ADMIN_TABLE . "
			SET admin_login = '" . $db->escape($admin_login) . "',
				admin_pwd   = '" . $db->escape($hasher->hash($admin_pass)) . "',
				admin_email = '" . $db->escape($admin_email) . "',
				admin_lang  = '$language'
			WHERE admin_id = 1";
		$sql_data[] = sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = 'urlsite'",
			CONFIG_TABLE,
			$db->escape($urlsite)
		);
		$sql_data[] = sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = 'path'",
			CONFIG_TABLE,
			$db->escape($urlscript)
		);
		$sql_data[] = sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = 'cookie_path'",
			CONFIG_TABLE,
			$db->escape($urlscript)
		);
		$sql_data[] = sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = 'language'",
			CONFIG_TABLE,
			$db->escape($language)
		);
		$sql_data[] = sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = 'mailing_startdate'",
			CONFIG_TABLE,
			time()
		);
		$sql_data[] = "UPDATE " . LISTE_TABLE . "
			SET form_url        = '" . $db->escape($urlsite.$urlscript.'subscribe.php') . "',
				sender_email    = '" . $db->escape($admin_email) . "',
				liste_startdate = " . time() . "
			WHERE liste_id = 1";
		
		exec_queries($sql_data);
		
		$db->close();
		
		$login_page = WA_ROOTDIR . '/admin/login.php';
		
		if( !defined('NL_INSTALLED') )
		{
			if( !($fw = @fopen(WA_ROOTDIR . '/includes/config.inc.php', 'w')) )
			{
				$output->addHiddenField('engine',  $infos['engine']);
				$output->addHiddenField('host',    $infos['host']);
				$output->addHiddenField('user',    $infos['user']);
				$output->addHiddenField('pass',    $infos['pass']);
				$output->addHiddenField('dbname',  $infos['dbname']);
				$output->addHiddenField('prefixe', $prefixe);
				
				$output->assign_block_vars('download_file', array(
					'L_TITLE'         => $lang['Result_install'],
					'L_DL_BUTTON'     => $lang['Button']['dl'],
					
					'MSG_RESULT'      => nl2br(sprintf($lang['Success_install_no_config'], sprintf('<a href="%s">', $login_page), '</a>')),						
					'S_HIDDEN_FIELDS' => $output->getHiddenFields()
				));
				
				$output->pparse('body');
				exit;
			}
			
			fwrite($fw, $config_file);
			fclose($fw);
		}
		
		message(sprintf($lang['Success_install'], sprintf('<a href="%s">', $login_page), '</a>'));
	}
}

if( !defined('NL_INSTALLED') )
{
	require WA_ROOTDIR . '/includes/functions.box.php';
	
	$db_box = '';
	foreach( $supported_db as $name => $data )
	{
		$selected = ( $infos['engine'] == $name ) ? ' selected="selected"' : '';
		$db_box .= '<option value="' . $name . '"' . $selected . '> ' . $data['Name'] . ' </option>';
	}
	
	if( $urlsite == '' )
	{
		$urlsite = 'http://' . server_info('HTTP_HOST');
	}
	
	if( $urlscript == '' )
	{
		$urlscript = dirname(server_info('PHP_SELF')).'/';
	}
	
	$l_explain = nl2br(sprintf(
		$lang['Welcome_in_install'],
		'<a href="' . WA_ROOTDIR . '/docs/readme.' . $lang['CONTENT_LANG'] . '.html">', '</a>',
		'<a href="' . WA_ROOTDIR . '/COPYING">', '</a>',
		'<a href="http://phpcodeur.net/wascripts/GPL">', '</a>'
	));
	
	if( $infos['host'] == '' ) {
		$infos['host'] = 'localhost';
	}
	
	$output->assign_block_vars('install', array(
		'L_EXPLAIN'         => $l_explain,
		'TITLE_DATABASE'    => $lang['Title']['database'],
		'TITLE_ADMIN'       => $lang['Title']['admin'],
		'TITLE_DIVERS'      => $lang['Title']['config_divers'],
		'L_DBTYPE'          => $lang['dbtype'],
		'L_DBHOST'          => $lang['dbhost'],
		'L_DBNAME'          => $lang['dbname'],
		'L_DBUSER'          => $lang['dbuser'],
		'L_DBPWD'           => $lang['dbpwd'],
		'L_PREFIXE'         => $lang['prefixe'],
		'L_DEFAULT_LANG'    => $lang['Default_lang'],
		'L_LOGIN'           => $lang['Login'],
		'L_PASS'            => $lang['Password'],
		'L_PASS_CONF'       => $lang['Conf_pass'],
		'L_EMAIL'           => $lang['Email_address'],
		'L_URLSITE'         => $lang['Urlsite'],
		'L_URLSCRIPT'       => $lang['Urlscript'],
		'L_URLSITE_NOTE'    => $lang['Urlsite_note'],
		'L_URLSCRIPT_NOTE'  => $lang['Urlscript_note'],
		'L_START_BUTTON'    => $lang['Start_install'],
		
		'DB_BOX'    => $db_box,
		'DBHOST'    => wan_htmlspecialchars($infos['host']),
		'DBNAME'    => wan_htmlspecialchars($infos['dbname']),
		'DBUSER'    => wan_htmlspecialchars($infos['user']),
		'PREFIXE'   => wan_htmlspecialchars($prefixe),
		'LOGIN'     => wan_htmlspecialchars($admin_login),
		'EMAIL'     => wan_htmlspecialchars($admin_email),
		'URLSITE'   => wan_htmlspecialchars($urlsite),
		'URLSCRIPT' => wan_htmlspecialchars($urlscript),
		'LANG_BOX'  => lang_box($language)
	));
}
else
{
	$output->assign_block_vars('reinstall', array(
		'L_EXPLAIN'      => nl2br($lang['Warning_reinstall']),
		'L_LOGIN'        => $lang['Login'],
		'L_PASS'         => $lang['Password'],
		'L_START_BUTTON' => $lang['Start_install'],
		
		'LOGIN' => wan_htmlspecialchars($admin_login)
	));
}

$output->assign_var('S_PREV_LANGUAGE', $language);

if( $error )
{
	$output->error_box($msg_error);
}

$output->pparse('body');
$output->page_footer();

