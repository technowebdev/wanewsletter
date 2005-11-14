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

define('IN_NEWSLETTER', true);
define('WA_ROOTDIR',    '.');

require WA_ROOTDIR . '/start.php';
require WA_ROOTDIR . '/includes/class.sessions.php';
require WA_ROOTDIR . '/includes/functions.validate.php';
include WA_ROOTDIR . '/includes/tags.inc.php';

if( !$nl_config['enable_profil_cp'] )
{
	load_settings();
	trigger_error('Profil_cp_disabled', MESSAGE);
}

//
// Instanciation d'une session
//
$session = new Session();

function check_login($email, $regkey = '', $passwd = '')
{
	global $db, $other_tags;
	
	//
	// R�cup�ration des champs des tags personnalis�s
	//
	if( count($other_tags) > 0 )
	{
		$fields_str = '';
		foreach( $other_tags AS $data )
		{
			$fields_str .= ', a.' . $data['column_name'];
		}
	}
	else
	{
		$fields_str = '';
	}
	
	$sql = "SELECT a.abo_id, a.abo_pseudo, a.abo_pwd, a.abo_email, a.abo_lang, a.abo_register_key,
			a.abo_register_date, a.abo_status, al.format, l.liste_id, l.liste_name, l.sender_email,
			l.return_email, l.liste_sig, l.liste_format, l.use_cron, l.liste_alias $fields_str
		FROM " . ABONNES_TABLE . " AS a
			INNER JOIN " . ABO_LISTE_TABLE . " AS al
			ON al.abo_id = a.abo_id
			INNER JOIN " . LISTE_TABLE . " AS l
			ON l.liste_id = al.liste_id
		WHERE LOWER(a.abo_email) = '" . $db->escape(strtolower($email)) . "'";
	if( $regkey != '' && $passwd != '' )
	{
		$sql .= " AND ( a.abo_pwd = '$passwd' OR a.abo_register_key = '$regkey' )";
	}
	
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible de r�cup�rer les donn�es de l\'abonn�', CRITICAL_ERROR);
	}
	
	if( $row = $db->fetch_array($result) )
	{
		$abodata = array();
		$abodata['id']       = $row['abo_id'];
		$abodata['pseudo']   = $row['abo_pseudo'];
		$abodata['passwd']   = $row['abo_pwd'];
		$abodata['email']    = $row['abo_email'];
		$abodata['language'] = $row['abo_lang'];
		$abodata['regdate']  = $row['abo_register_date'];
		$abodata['regkey']   = $row['abo_register_key'];
		$abodata['status']   = $row['abo_status'];
		
		foreach( $other_tags AS $data )
		{
			$abodata[$data['column_name']] = $row[$data['column_name']];
		}
		
		$abodata['listes'] = array();
		
		do
		{
			$abodata['listes'][$row['liste_id']] = $row;
		}
		while( $row = $db->fetch_array($result) );
		
		return $abodata;
	}
	else
	{
		return false;
	}
}

$mode  = ( !empty($_REQUEST['mode']) ) ? $_REQUEST['mode'] : '';
$email = ( !empty($_REQUEST['email']) ) ? trim($_REQUEST['email']) : '';

//
// V�rification de l'authentification
//
if( $mode != 'login' && $mode != 'sendkey' )
{
	if( !empty($_COOKIE[$nl_config['cookie_name'] . '_abo']) )
	{
		$data = (array) unserialize($_COOKIE[$nl_config['cookie_name'] . '_abo']);
	}
	else
	{
		$data = array();
	}
	
	if( isset($data['email']) && isset($data['key']) && validate_pass($data['key']) )
	{
		if( $mode == 'logout' )
		{
			$session->send_cookie('abo', '', (time() - 3600));
			
			$mode = 'login';
		}
		else
		{
			$abodata = check_login($data['email'], $data['key'], $data['key']);
			if( !is_array($abodata) )
			{
				$mode = 'login';
			}
		}
	}
	else
	{
		$mode = 'login';
	}
}
//
// Fin de la v�rification
//

$language = ( $mode == 'login' || $mode == 'sendkey' ) ? $nl_config['language'] : $abodata['language'];
load_settings(array('admin_lang' => $language));

switch( $mode )
{
	case 'login':
		if( isset($_POST['submit']) )
		{
			$regkey = ( !empty($_POST['passwd']) ) ? trim($_POST['passwd']) : '';
			$result = check_email($email, -1, '', true);
			
			if( !$result['error'] )
			{
				if( !empty($regkey) && validate_pass($regkey) && ($abodata = check_login($email, $regkey, md5($regkey))) )
				{
					if( $abodata['status'] == ABO_ACTIF )
					{
						$key  = ( $regkey == $abodata['regkey'] ) ? $regkey : md5($regkey);
						$data = serialize(array('email' => $abodata['email'], 'key' => $key));
						
						$session->send_cookie('abo', $data, (time() + 3600));
						
						Location('profil_cp.php');
					}
					
					trigger_error('Inactive_account', MESSAGE);
				}
				else
				{
					$error = TRUE;
					$msg_error[] = $lang['Message']['Error_login'];
				}
			}
			else
			{
				$error = TRUE;
				$msg_error[] = $result['message'];
			}
		}
		
		$output->page_header();
		
		$output->set_filenames(array(
			'body' => 'login_body.tpl'
		));
		
		$output->assign_vars(array(
			'TITLE'          => $lang['Module']['login'],
			'L_LOGIN'        => $lang['Account_login'],
			'L_PASS'         => $lang['Account_pass'],
			'L_SENDKEY'      => $lang['Lost_key'],
			'L_VALID_BUTTON' => $lang['Button']['valid']
		));
		
		$output->pparse('body');
		break;
	
	case 'sendkey':
		if( isset($_POST['submit']) )
		{
			$result = check_email($email, -1, '', true);
			
			if( !$result['error'] )
			{
				if( $abodata = check_login($email) )
				{
					list($liste_id, $listdata) = each($abodata['listes']);
					
					require WAMAILER_DIR . '/class.mailer.php';
					
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
					
					if( $abodata['pseudo'] != '' )
					{
						$address = array($abodata['pseudo'] => $abodata['email']);
					}
					else
					{
						$address = $abodata['email'];
					}
					
					$mailer->set_from($listdata['sender_email'], unhtmlspecialchars($listdata['liste_name']));
					$mailer->set_address($address);
					$mailer->set_subject($lang['Subject_email']['Sendkey']);
					$mailer->set_return_path($listdata['return_email']);
					
					$mailer->use_template('account_info', array(
						'EMAIL'   => $abodata['email'],
						'CODE'    => $abodata['regkey'],
						'URLSITE' => $nl_config['urlsite'],
						'SIG'     => $listdata['liste_sig']
					));
					
					if( !$mailer->send() )
					{
						trigger_error('Failed_sending', ERROR);
					}
					
					trigger_error('IDs_sended', MESSAGE);
				}
				else
				{
					$error = TRUE;
					$msg_error[] = $lang['Message']['Unknown_email'];
				}
			}
			else
			{
				$error = TRUE;
				$msg_error[] = $result['message'];
			}
		}
		
		$output->page_header();
		
		$output->set_filenames(array(
			'body' => 'sendkey_body.tpl'
		));
		
		$output->assign_vars(array(
			'TITLE'          => $lang['Title']['sendkey'],
			'L_EXPLAIN'      => nl2br($lang['Explain']['sendkey']),
			'L_LOGIN'        => $lang['Account_login'],
			'L_VALID_BUTTON' => $lang['Button']['valid']
		));
		
		$output->pparse('body');
		break;
	
	case 'editprofile':
		if( isset($_POST['submit']) )
		{
			$vararray = array('pseudo', 'language', 'current_pass', 'new_pass', 'confirm_pass');
			foreach( $vararray AS $varname )
			{
				${$varname} = ( !empty($_POST[$varname]) ) ? trim($_POST[$varname]) : '';
			}
			
			if( $language == '' || !validate_lang($language) )
			{
				$language = $nl_config['language'];
			}
			
			if( $current_pass != '' && md5($current_pass) != $abodata['passwd'] )
			{
				$error = TRUE;
				$msg_error[] = $lang['Message']['Error_login'];
			}
			
			$set_password = FALSE;
			if( $new_pass != '' && $confirm_pass != '' )
			{
				if( !validate_pass($new_pass) )
				{
					$error = TRUE;
					$msg_error[] = $lang['Message']['Alphanum_pass'];
				}
				else if( $new_pass != $confirm_pass )
				{
					$error = TRUE;
					$msg_error[] = $lang['Message']['Bad_confirm_pass'];
				}
				
				$set_password = TRUE;
			}
			
			if( !$error )
			{
				$sql_data = array(
					'abo_pseudo' => htmlspecialchars($pseudo),
					'abo_lang'   => $language
				);
				
				if( $set_password )
				{
					$sql_data['abo_pwd'] = md5($new_pass);
				}
				
				foreach( $other_tags AS $data )
				{
					if( !empty($_POST[$data['column_name']]) )
					{
						$sql_data[$data['column_name']] = trim($_POST[$data['column_name']]);
					}
				}
				
				if( !$db->query_build('UPDATE', ABONNES_TABLE, $sql_data, array('abo_id' => $abodata['id'])) )
				{
					trigger_error('Impossible de mettre le profil � jour', ERROR);
				}
				
				$output->redirect('profil_cp.php', 4);
				trigger_error('Profile_updated', MESSAGE);
			}
		}
		
		require WA_ROOTDIR . '/includes/functions.box.php';
		
		$output->page_header();
		
		$output->set_filenames(array(
			'body' => 'editprofile_body.tpl'
		));
		
		$output->assign_vars(array(
			'TITLE'          => $lang['Module']['editprofile'],
			'L_EXPLAIN'      => nl2br($lang['Explain']['editprofile']),
			'L_EMAIL'        => $lang['Email_address'],
			'L_PSEUDO'       => $lang['Abo_pseudo'],
			'L_LANG'         => $lang['Default_lang'],
			'L_NEW_PASS'     => $lang['New_pass'],
			'L_CONFIRM_PASS' => $lang['Conf_pass'],
			'L_VALID_BUTTON' => $lang['Button']['valid'],
			
			'EMAIL'    => $abodata['email'],
			'PSEUDO'   => $abodata['pseudo'],
			'LANG_BOX' => lang_box($abodata['language'])
		));
		
		foreach( $other_tags AS $data )
		{
			$output->assign_var($data['tag_name'], htmlspecialchars($abodata[$data['column_name']]));
		}
		
		if( $abodata['passwd'] != '' )
		{
			$output->assign_block_vars('password', array(
				'L_PASS' => $lang['Password']
			));
		}
		
		$output->pparse('body');
		break;
	
	case 'archives':
		if( isset($_POST['submit']) )
		{
			$listlog = ( !empty($_POST['log']) ) ? (array) $_POST['log'] : array();
			
			$sql_log_id = array();
			foreach( $listlog AS $liste_id => $logs )
			{
				if( isset($abodata['listes'][$liste_id]) )
				{
					$logs = array_map('intval', $logs);
					$sql_log_id = array_merge($sql_log_id, $logs);
				}
			}
			
			if( count($sql_log_id) == 0 )
			{
				trigger_error('No_log_id', MESSAGE);
			}
			
			$sql = "SELECT lf.log_id, jf.file_id, jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype 
				FROM " . JOINED_FILES_TABLE . " AS jf
					INNER JOIN " . LOG_FILES_TABLE . " AS lf
					ON lf.file_id = jf.file_id
						AND lf.log_id IN(" . implode(', ', $sql_log_id) . ")";
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir la liste des fichiers joints', ERROR);
			}
			
			$files = array();
			while( $row = $db->fetch_array($result) )
			{
				$files[$row['log_id']][] = $row;
			}
			
			$sql = "SELECT liste_id, log_id, log_subject, log_body_text, log_body_html 
				FROM " . LOG_TABLE . " 
				WHERE log_id IN(" . implode(', ', $sql_log_id) . ") 
					AND log_status = " . STATUS_SENDED;
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible de r�cup�rer la liste des archives', ERROR);
			}
			
			require WAMAILER_DIR . '/class.mailer.php';
			
			//
			// Initialisation de la classe mailer
			//
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
			
			if( $abodata['pseudo'] != '' )
			{
				$address = array($abodata['pseudo'] => $abodata['email']);
			}
			else
			{
				$address = $abodata['email'];
			}
			
			$lang['CHARSET'] = strtoupper($lang['CHARSET']);
			
			while( $row = $db->fetch_array($result) )
			{
				$listdata = $abodata['listes'][$row['liste_id']];
				$format   = $abodata['listes'][$row['liste_id']]['format'];
				
				if( $lang['CHARSET'] == 'ISO-8859-1' )
				{
					$row['log_subject'] = purge_latin1($row['log_subject'], true);
				}
				
				$mailer->clear_all();
				$mailer->set_from($listdata['sender_email'], unhtmlspecialchars($listdata['liste_name']));
				$mailer->set_address($address);
				$mailer->set_format($format);
				$mailer->set_subject($row['log_subject']);
				
				if( $listdata['return_email'] != '' )
				{
					$mailer->set_return_path($listdata['return_email']);
				}
				
				if( $format == FORMAT_TEXTE )
				{
					$body = $row['log_body_text'];
					if( $lang['CHARSET'] == 'ISO-8859-1' )
					{
						$body = purge_latin1($body, true);
					}
				}
				else
				{
					$body = $row['log_body_html'];
					if( $lang['CHARSET'] == 'ISO-8859-1' )
					{
						$body = purge_latin1($body);
					}
				}
				
				//
				// Ajout du lien de d�sinscription, selon le format utilis�
				//
				if( $listdata['use_cron'] )
				{
					$liste_email = ( !empty($listdata['liste_alias']) ) ? $listdata['liste_alias'] : $listdata['sender_email'];
					
					if( $format == FORMAT_TEXTE )
					{
						$link = $liste_email;
					}
					else
					{
						$link = '<a href="mailto:' . $liste_email . '?subject=unsubscribe">' . $lang['Label_link'] . '</a>';
					}
				}
				else
				{
					$tmp_link  = $listdata['form_url'] . ( ( strstr($listdata['form_url'], '?') ) ? '&' : '?' );
					$tmp_link .= 'action=desinscription&email={EMAIL}&code={CODE}&liste=' . $listdata['liste_id'];
					
					if( $format == FORMAT_TEXTE )
					{
						$link = $tmp_link;
					}
					else
					{
						$link = '<a href="' . htmlspecialchars($tmp_link) . '">' . $lang['Label_link'] . '</a>';
					}
				}
				
				$body = str_replace('{LINKS}', $link, $body);
				$mailer->set_message($body);
				
				//
				// On s'occupe maintenant des fichiers joints ou incorpor�s 
				// Si les fichiers sont stock�s sur un serveur ftp, on les rapatrie le temps du flot d'envoi
				//
				if( isset($files[$row['log_id']]) && count($files[$row['log_id']]) > 0 )
				{
					$total_files = count($files[$row['log_id']]);
					$tmp_files	 = array();
					
					require WA_ROOTDIR . '/includes/class.attach.php';
					$attach = new Attach();
					
					hasCidReferences($body, $refs);
					
					for( $i = 0; $i < $total_files; $i++ )
					{
						$real_name     = $files[$row['log_id']][$i]['file_real_name'];
						$physical_name = $files[$row['log_id']][$i]['file_physical_name'];
						$mime_type     = $files[$row['log_id']][$i]['file_mimetype'];
						
						$error = FALSE;
						$msg   = array();
						
						$attach->joined_file_exists($physical_name, $error, $msg);
						
						if( $error )
						{
							$error = FALSE;
							continue;
						}
						
						if( $nl_config['use_ftp'] )
						{
							$file_path = $attach->ftp_to_tmp($files[$row['log_id']][$i]);
							array_push($tmp_files, $file_path);
						}
						else
						{
							$file_path = WA_ROOTDIR . '/' . $nl_config['upload_path'] . $physical_name;
						}
						
						if( is_array($refs) && in_array($real_name, $refs) )
						{
							$embedded = TRUE;
						}
						else
						{
							$embedded = FALSE;
						}
						
						$mailer->attachment($file_path, $real_name, 'attachment', $mime_type, $embedded);
					}
				}
				
				//
				// Traitement des tags et tags personnalis�s
				//
				$tags_replace = array();
				
				if( $abodata['pseudo'] != '' )
				{
					$tags_replace['NAME'] = ( $format == FORMAT_HTML ) ? $abodata['pseudo'] : unhtmlspecialchars($abodata['pseudo']);
				}
				else
				{
					$tags_replace['NAME'] = '';
				}
				
				if( count($other_tags) > 0 )
				{
					foreach( $other_tags AS $data )
					{
						if( $abodata[$data['column_name']] != '' )
						{
							if( !is_numeric($abodata[$data['column_name']]) && $format == FORMAT_HTML )
							{
								$tags_replace[$data['tag_name']] = htmlspecialchars($abodata[$data['column_name']]);
							}
							else
							{
								$tags_replace[$data['tag_name']] = $abodata[$data['column_name']];
							}
							
							continue;
						}
						
						$tags_replace[$data['tag_name']] = '';
					}
				}
				
				if( !$listdata['use_cron'] )
				{
					$tags_replace = array_merge($tags_replace, array(
						'CODE'  => $abodata['regkey'],
						'EMAIL' => rawurlencode($abodata['email'])
					));
				}
				
				$mailer->assign_tags($tags_replace);
				
				// envoi
				if( !$mailer->send() )
				{
					trigger_error('Failed_sending', ERROR);
				}
			}
			
			trigger_error(sprintf($lang['Message']['Logs_sent'], $abodata['email']), MESSAGE);
		}
		
		$sql_list_id = array();
		foreach( $abodata['listes'] AS $liste_id => $listdata )
		{
			array_push($sql_list_id, $liste_id);
		}
		
		$sql = "SELECT log_id, liste_id, log_subject, log_date 
			FROM " . LOG_TABLE . " 
			WHERE liste_id IN(" . implode(', ', $sql_list_id) . ") 
				AND log_status = " . STATUS_SENDED . " 
			ORDER BY log_date DESC";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible de r�cup�rer la liste des archives', ERROR);
		}
		
		while( $row = $db->fetch_array($result) )
		{
			$abodata['listes'][$row['liste_id']]['archives'][] = $row;
		}
		
		$output->addHiddenfield('mode', 'archives');
		
		$output->page_header();
		
		$output->set_filenames(array(
			'body' => 'archives_body.tpl'
		));
		
		$output->assign_vars(array(
			'TITLE'           => $lang['Title']['archives'],
			'L_EXPLAIN'       => $lang['Explain']['archives'],
			'L_VALID_BUTTON'  => $lang['Button']['valid'],
			
			'S_HIDDEN_FIELDS' => $output->getHiddenFields()
		));
		
		foreach( $abodata['listes'] AS $liste_id => $listdata )
		{
			if( !isset($abodata['listes'][$liste_id]['archives']) )
			{
				continue;
			}
			
			$num_logs = count($abodata['listes'][$liste_id]['archives']);
			$size     = ( $num_logs > 8 ) ? 8 : $num_logs;
			
			$select_log = '<select id="liste_' . $liste_id . '" name="log[' . $liste_id . '][]" size="' . $size . '" multiple="multiple" style="min-width: 200px;">';
			for( $i = 0; $i < $num_logs; $i++ )
			{
				$logrow = $abodata['listes'][$liste_id]['archives'][$i];
				
				$select_log .= '<option value="' . $logrow['log_id'] . '"> &#8211; ' . htmlspecialchars(cut_str($logrow['log_subject'], 40));
				$select_log .= ' [' . convert_time('d/m/Y', $logrow['log_date']) . ']</option>';
			}
			$select_log .= '</select>';
			
			$output->assign_block_vars('listerow', array(
				'LISTE_ID'   => $liste_id,
				'LISTE_NAME' => $listdata['liste_name'],
				'SELECT_LOG' => $select_log
			));
		}
		
		$output->pparse('body');
		break;
	
	default:
		$output->page_header();
		
		$output->set_filenames(array(
			'body' => 'index_body.tpl'
		));
		
		$output->assign_vars(array(
			'TITLE'     => $lang['Title']['profil_cp'],
			'L_EXPLAIN' => nl2br($lang['Welcome_profil_cp'])
		));
		
		$output->pparse('body');
		break;
}

$output->page_footer();
?>