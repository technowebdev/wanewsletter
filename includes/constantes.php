<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aur�lien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

//
// Version correspondant au code source en place sur le serveur.
// Remplace la constante obsol�te WA_VERSION, jadis d�finie dans le fichier de configuration.
//
define('WANEWSLETTER_VERSION', '2.4-beta3');

//
// identifiant de version des tables du script.
// Doit correspondre � l'entr�e 'db_version' dans la configuration, sinon,
// le script invite l'utilisateur � lancer la proc�dure de mise � jour des tables
//
define('WANEWSLETTER_DB_VERSION', 14);

//
// Modes de d�bogage du script
//
// Le script annonce les erreurs critiques, sans donner de d�tails
define('DEBUG_LEVEL_QUIET',  1);
// Le script affiche les erreurs PHP et SQL en donnant des d�tails
define('DEBUG_LEVEL_NORMAL', 2);
// Le script affiche aussi les erreurs non inclues dans le niveau d'erreurs PHP
// configur� par error_reporting() ou masqu�es avec l'op�rateur @
define('DEBUG_LEVEL_ALL',    3);

//
// Configure le niveau de d�bogage souhait�
//
define('DEBUG_MODE', DEBUG_LEVEL_NORMAL);

//
// Pour visualiser le temps d'ex�cution du script et le nombre de requ�tes effectu�es
//
define('DEV_INFOS', true);
//define('DEV_INFOS', false);

//
// Active/D�sactive l'affichage des messages d'erreur en pied de page.
// Si false, les erreurs sont affich�es d�s qu'elles sont trait�es.
//
define('DISPLAY_ERRORS_IN_LOG', true);

//
// Active/D�sactive le passage automatique � l'UTF-8 au moment de l'envoi en pr�sence de
// caract�res invalides provenant de Windows-1252 dans les newsletters.
//
// Si cette constante est plac�e � TRUE, les caract�res en cause subiront une transformation
// vers un caract�re simple ou compos� graphiquement proche (voir la fonction purge_latin1()
// dans le fichier includes/functions.php).
//
define('TRANSLITE_INVALID_CHARS', false);

//
// Prise en compte de l'authentification HTTP pour la connexion automatique
//
define('ENABLE_HTTP_AUTHENTICATION', true);


//
// Il est recommand� de ne rien modifier au-del� de cette ligne
//

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
define('STATUS_SENT',    2);
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
define('ADMIN_LEVEL', 2);
define('USER_LEVEL',  1);

//
// divers
//
define('SUBSCRIBE_NOTIFY_YES', 1);
define('SUBSCRIBE_NOTIFY_NO',  0);
define('UNSUBSCRIBE_NOTIFY_YES', 1);
define('UNSUBSCRIBE_NOTIFY_NO',  0);

define('MAX_IMPORT', 10000);

define('ENGINE_BCC',  1);
define('ENGINE_UNIQ', 2);

define('CONFIRM_ALWAYS', 2);
define('CONFIRM_ONCE',   1);
define('CONFIRM_NONE',   0);

//
// On v�rifie si l'upload est autoris� sur le serveur
//
if (config_status('file_uploads')) {
	function get_integer_byte_value($size)
	{
		if (preg_match('/^([0-9]+)([KMG])$/i', $size, $m)) {
			switch (strtoupper($m[2])) {
				case 'K':
					$size = ($m[1] * 1024);
					break;
				case 'M':
					$size = ($m[1] * 1024 * 1024);
					break;
				case 'G': // Since php 5.1.0
					$size = ($m[1] * 1024 * 1024 * 1024);
					break;
			}
		}
		else {
			$size = intval($size);
		}

		return $size;
	}

	if (!($filesize = config_value('upload_max_filesize'))) {
        $filesize = '2M'; // 2 M�ga-Octets
    }
	$upload_max_size = get_integer_byte_value($filesize);

    if ($postsize = config_value('post_max_size')) {
        $postsize = get_integer_byte_value($postsize);
        if ($postsize < $upload_max_size) {
            $upload_max_size = $postsize;
        }
    }

	define('FILE_UPLOADS_ON', true);
	define('MAX_FILE_SIZE',   $upload_max_size);
}
else {
	define('FILE_UPLOADS_ON', false);
	define('MAX_FILE_SIZE',   0);
}

//
// Infos sur l'utilisateur
//
$user_agent = server_info('HTTP_USER_AGENT');

if ($user_agent != '') {
	if (stristr($user_agent, 'win')) {
		define('WA_USER_OS', 'win');
	}
	else if (stristr($user_agent, 'mac')) {
		define('WA_USER_OS', 'mac');
	}
	else if (stristr($user_agent, 'linux')) {
		define('WA_USER_OS', 'linux');
	}
	else {
		define('WA_USER_OS', 'other');
	}

	if (stristr($user_agent, 'opera')) {
		define('WA_USER_BROWSER', 'opera');
	}
	else if (stristr($user_agent, 'msie')) {
		define('WA_USER_BROWSER', 'msie');
	}
	else if (stristr($user_agent, 'konqueror')) {
		define('WA_USER_BROWSER', 'konqueror');
	}
	else if (stristr($user_agent, 'mozilla')) {
		define('WA_USER_BROWSER', 'mozilla');
	}
	else {
		define('WA_USER_BROWSER', 'other');
	}
}
else {
	define('WA_USER_OS',      'other');
	define('WA_USER_BROWSER', 'other');
}

//
// Signature du script pour divers cas de figure (ent�te X-Mailer dans les emails
// envoy�s, ent�te User-Agent lors des requ�tes HTTP, etc)
//
define('WA_SIGNATURE', sprintf('Wanewsletter/%s', WANEWSLETTER_VERSION));
define('WA_X_MAILER', WA_SIGNATURE);

//
// Utilis�es dans le cadre de la classe de v�rification de mise � jour
//
define('WA_DOWNLOAD_PAGE', 'http://phpcodeur.net/wascripts/wanewsletter/telecharger');
define('WA_CHECK_UPDATE_URL', 'http://phpcodeur.net/wascripts/wanewsletter/releases/latest/version');
define('WA_CHECK_UPDATE_CACHE', 'wa-check-update.cache');
define('WA_CHECK_UPDATE_CACHE_TTL', 3600);

//
// D�claration des dossiers et fichiers sp�ciaux utilis�s par le script
//
define('WA_LOGSDIR',  str_replace('~', WA_ROOTDIR, rtrim($logs_dir, '/')));
define('WA_STATSDIR', str_replace('~', WA_ROOTDIR, rtrim($stats_dir, '/')));
define('WA_TMPDIR',   str_replace('~', WA_ROOTDIR, rtrim($tmp_dir, '/')));

define('WAMAILER_DIR', WA_ROOTDIR . '/includes/wamailer');
define('WA_LOCKFILE',  WA_TMPDIR . '/liste-%d.lock');

