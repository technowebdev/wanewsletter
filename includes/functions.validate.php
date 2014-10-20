<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aur�lien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('FUNCTIONS_VALIDATE_INC') ) {

define('FUNCTIONS_VALIDATE_INC', true);

/**
 * @param string $pseudo
 *
 * @return boolean
 */
function validate_pseudo($pseudo)
{
	return (strlen($pseudo) >= 2 && strlen($pseudo) <= 30);
}

/**
 * @param string $passwd
 *
 * @return boolean
 */
function validate_pass($passwd)
{
	return (bool) preg_match('/^[\x20-\x7E]{6,1024}$/', $passwd);
}

/**
 * @param string $language
 *
 * @return boolean
 */
function validate_lang($language)
{
	return (bool) (preg_match('/^[\w_-]+$/', $language) &&
		file_exists(WA_ROOTDIR . '/language/lang_' . $language . '.php')
	);
}

}
