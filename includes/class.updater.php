<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aur�lien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('CLASS_UPDATER_INC') ) {

define('CLASS_UPDATER_INC', true);

class Wa_Updater {
	
	var $cache    = '';
	var $cacheTtl = 0;
	var $url      = '';
	
	function check($complete = false)
	{
		$result = false;
		$data   = '';
		
		if( is_readable($this->cache) && filemtime($this->cache) > (time() - $this->cacheTtl) )
		{
			$data = file_get_contents($this->cache);
		}
		else if( $complete )
		{
			$result = http_get_contents($this->url, $errstr);
			$data = $result['data'];
			
			if( $data !== false )
			{
				file_put_contents($this->cache, $data);
			}
		}
		
		if( $data != '' )
		{
			$result = intval(version_compare(WANEWSLETTER_VERSION, trim($data), '<'));
		}
		
		return $result;
	}
}

}
