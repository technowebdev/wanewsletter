<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

if (substr($_SERVER['SCRIPT_FILENAME'], -8) == '.inc.php') {
	exit('<b>No hacking</b>');
}

if (!defined('WA_ROOTDIR')) {
	define('WA_ROOTDIR', str_replace('\\', '/', dirname(__DIR__)));
}

set_include_path('.'.PATH_SEPARATOR.WA_ROOTDIR);

// $default_error_reporting est utilisé ultérieurement dans le gestionnaire d'erreurs
define(__NAMESPACE__.'\\DEFAULT_ERROR_REPORTING', (E_ALL & ~(E_STRICT|E_DEPRECATED)));
error_reporting(DEFAULT_ERROR_REPORTING);

$starttime = array_sum(explode(' ', microtime()));

//
// On vérifie proprement la présence des dépendances.
// Évite que l'utilisateur prenne un méchant et énigmatique fatal error sur le require() suivant.
//
if (!file_exists(WA_ROOTDIR . '/vendor/autoload.php')) {
	echo "Please first install the dependencies using the command: ";
	echo "<samp>composer install</samp><br>";
	echo "See the <a href='https://getcomposer.org/'>official website of Composer</a>.";
	exit;
}

require 'includes/constantes.php';
require 'includes/compat.inc.php';
require 'includes/functions.php';
require 'includes/functions.wrapper.php';
require 'vendor/autoload.php';

//
// Configuration des gestionnaires d'erreurs et d'exceptions
//
set_error_handler(__NAMESPACE__.'\\wan_error_handler');
set_exception_handler(__NAMESPACE__.'\\wan_exception_handler');

//
// Chargement automatique des classes
//
spl_autoload_register(function ($classname) {
	$rootdir = dirname(__DIR__);
	$prefix  = '';

	if (strpos($classname, '\\')) {
		list($prefix, $classname) = explode('\\', $classname, 2);
	}

	if ($prefix != 'Wanewsletter') {
		return null;
	}

	if (strpos($classname, '\\')) {
		// Chemin includes/<namespace>/<classname>.php
		$filename = sprintf('%s/includes/%s.php', $rootdir, str_replace('\\', '/', $classname));
	}
	else {
		// Ancien nommage de fichiers. Chemin includes/class.<classname>.php
		$filename = sprintf('%s/includes/class.%s.php', $rootdir, strtolower($classname));
	}

	if (is_readable($filename)) {
		require $filename;
	}
});

//
// Initialisation du système d’affichage
//
if (filter_input(INPUT_GET, 'output') == 'json') {
	$output = new Output\Json;
}
else if (check_cli()) {
	$output = new Output\CommandLine;
}
else {
	$output = new Output\Html;
}

//
// Initialisation des variables pour éviter toute injection malveillante de code
//
$error     = false;
$nl_config = $lang = $datetime = $msg_error = [];

//
// Chargement de la configuration de base
//
load_config();

//
// Initialisation de patchwork/utf8
//
\Patchwork\Utf8\Bootup::initAll();

//
// Initialisation  des données sur les tables et de la connexion
// à la base de données (doit être placé après load_config()).
//
require 'includes/wadb_init.php';
