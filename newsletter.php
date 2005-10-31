<?php
/**
 * Copyright (c) 2002-2006 Aur�lien Maille
 * 
 * This file is part of Wanewsletter.
 * 
 * Wanewsletter is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * Wanewsletter is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Wanewsletter; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * @version $Id$
 */

if( !defined('IN_WA_FORM') && !defined('IN_SUBSCRIBE') )
{
	exit('<b>No hacking</b>');
}

define('IN_NEWSLETTER', true);
define('WA_ROOTDIR', $waroot);

$default_magic_quotes_runtime = get_magic_quotes_runtime();
$default_error_reporting      = error_reporting(E_ALL);

require WA_ROOTDIR . '/start.php';
require WA_ROOTDIR . '/includes/functions.validate.php';

if( !empty($language) && validate_lang($language) )
{
	load_settings(array('admin_lang' => $language));
	unset($language);
}
else
{
	load_settings();
}

$message = '';

$vararray = array('action', 'email', 'code', 'liste');
foreach( $vararray AS $varname )
{
	${$varname} = ( !empty($_REQUEST[$varname]) ) ? $_REQUEST[$varname] : '';
}

if( $action != '' )
{
	$sql = 'SELECT * FROM ' . LISTE_TABLE . ' 
		WHERE liste_id = ' . intval($liste);
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir les donn�es sur la liste', ERROR);
	}
	else if( $listdata = $db->fetch_array($result) )
	{
		//
		// Purge des �ventuelles inscriptions d�pass�es
		// pour parer au cas d'une r�inscription
		// (Moyen de faire autrement pour �viter cette requ�te - voir plus tard)
		//
		purge_liste();
		
		require WA_ROOTDIR . '/includes/class.form.php';
		require WAMAILER_DIR . '/class.mailer.php';
		include WA_ROOTDIR . '/includes/functions.stats.php';
		
		$mailer = new Mailer(WA_ROOTDIR . '/language/email_' . $nl_config['language'] . '/');
		
		if( $nl_config['use_smtp'] )
		{
			$mailer->smtp_path = WAMAILER_DIR . '/';
			$mailer->use_smtp(
				$nl_config['smtp_host'],
				$nl_config['smtp_port'],
				$nl_config['smtp_user'],
				$nl_config['smtp_pass']
			);
		}
		
		$mailer->correctRpath = !is_disabled_func('ini_set');
		
		$mailer->set_charset($lang['CHARSET']);
		$mailer->set_format(FORMAT_TEXTE);
		
		$wanewsletter = new Wanewsletter($listdata);
		
		if( $wanewsletter->account_info($email, '', $code, $action) )
		{
			switch( $action )
			{
				case 'inscription':
					$wanewsletter->subscribe();
					break;
				
				case 'confirmation':
					$wanewsletter->confirm();
					break;
				
				case 'desinscription':
					$wanewsletter->unsubscribe();
					break;
				
				case 'setformat':
					$wanewsletter->setformat();
					break;
			}
		}
		
		if( empty($message) )
		{
			$message = $wanewsletter->message;
		}
		
		if( $wanewsletter->update_stats && function_exists('update_stats') )
		{
			update_stats($listdata);
		}
	}
	else
	{
		$message = $lang['Message']['Unknown_list'];
	}
}

if( defined('IN_WA_FORM') )
{
	//
	// On r�active le gestionnaire d'erreur pr�c�dent
	//
	@restore_error_handler();
	
	echo $message;
}

//
// remise des param�tres par d�faut
//
error_reporting($default_error_reporting);

set_magic_quotes_runtime($default_magic_quotes_runtime);

?>