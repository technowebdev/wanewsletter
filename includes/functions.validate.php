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

/**
 * check_email()
 * 
 * V�rification de l'email
 * 
 * @param string  $email     Email � v�rifier
 * @param integer $liste     Id de la liste concern�e
 * @param string  $action    Action en cours
 * 
 * @return array
 */
function check_email($email, $liste = 0, $action = '', $disable_check_mx = false)
{
	global $waroot, $db, $nl_config, $lang;
	
	if( !class_exists('Mailer') )
	{
		include $waroot . 'includes/class.mailer.php';
	}
	
	//
	// V�rification syntaxique de l'email
	//
	$result = Mailer::validate_email($email);
	
	if( !$result )
	{
		return array('error' => true, 'message' => $lang['Message']['Invalid_email']);
	}
	
	$row = '';
	if( $liste > 0 )
	{
		$sql = 'SELECT ban_email FROM ' . BANLIST_TABLE . ' 
			WHERE liste_id = ' . $liste;
		if( $result = $db->query($sql) )
		{
			while( $row = $db->fetch_array($result) )
			{
				if( preg_match('/\b' . str_replace('*', '.*?', $row['ban_email']) . '\b/i', $email) )
				{
					return array('error' => true, 'message' => $lang['Message']['Email_banned']);
				}
			}
		}
		
		switch( DATABASE )
		{
			case 'postgre':
				$sql = "SELECT a.*, 1 AS is_registered 
					FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al 
					WHERE LOWER(a.abo_email) = '" . $db->escape(strtolower($email)) . "' 
						AND a.abo_id = al.abo_id 
						AND al.liste_id = $liste 
						UNION(
							SELECT a.*, 0 AS is_registered 
							FROM " . ABONNES_TABLE . " AS a 
							WHERE LOWER(a.abo_email) = '" . $db->escape(strtolower($email)) . "' 
								AND NOT EXISTS(
									SELECT abo_id 
									FROM " . ABO_LISTE_TABLE . " AS al 
									WHERE al.abo_id = a.abo_id 
										AND al.liste_id = $liste
							)
						)";
				break;
			
			default:
				$sql = "SELECT a.*, COUNT(al.abo_id) AS is_registered 
					FROM " . ABONNES_TABLE . " AS a 
					LEFT JOIN " . ABO_LISTE_TABLE . " AS al ON a.abo_id = al.abo_id 
						AND al.liste_id = $liste 
					WHERE LOWER(a.abo_email) = '" . $db->escape(strtolower($email)) . "' 
					GROUP BY al.abo_id";
				break;
		}
		
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible de tester les tables d\'inscriptions', ERROR);
		}
		
		if( $row = $db->fetch_array($result) )
		{
			if( $row['is_registered'] )
			{
				if( $action == 'inscription' )
				{
					return array('error' => true, 'message' => $lang['Message']['Allready_reg']);
				}
			}
			else
			{
				if( $action == 'desinscription' || $action == 'setformat' )
				{
					return array('error' => true, 'message' => $lang['Message']['Unknown_email']);
				}
			}
			
			if( $action == 'confirmation' && $row['abo_status'] == ABO_ACTIF )
			{
				return array('error' => true, 'message' => $lang['Message']['Allready_confirm']);
			}
		}
		else if( $action != 'inscription' )
		{
			return array('error' => true, 'message' => $lang['Message']['Unknown_email']);
		}
	}
	
	if( !$disable_check_mx && $nl_config['check_email_mx'] && ( !$liste || !is_array($row) ) )
	{
		//
		// V�rification de l'existence d'un Mail eXchanger sur le domaine de l'email, 
		// et v�rification de l'existence du compte associ� (La v�rification de l'existence du 
		// compte n'est toutefois pas infaillible, les serveurs smtp refusant parfois le relaying, 
		// c'est � dire de traiter les demandes �manant d'un entit� ext�rieure � leur r�seau, et 
		// pour une adresse email ext�rieure � ce r�seau)
		//
		$mailer = new Mailer();
		$mailer->smtp_path = $waroot . 'includes/';
		$result = $mailer->validate_email_mx($email);
		
		if( !$result )
		{
			return array('error' => true, 'message' => $lang['Message']['Unrecognized_email']);
		}
	}
	
	return array('error' => false, 'abo_data' => $row);
}

function validate_pseudo($pseudo)
{
	return ( strlen($pseudo) >= 2 && strlen($pseudo) <= 30 );
}

function validate_pass($password)
{
	return preg_match('/^[[:alnum:]][[:alnum:]_-]{2,30}[[:alnum:]]$/', $password);
}

function validate_lang($language)
{
	global $waroot;
	
	return preg_match('/^[\w_-]+$/', $language) && file_exists($waroot . 'language/lang_' . $language . '.php');
}

?>