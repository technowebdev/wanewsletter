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
 * @author	Bobe <wascripts@phpcodeur.net>
 * @link	http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * @version $Id$
 */

define('IN_NEWSLETTER', true);

require './pagestart.php';

$mode      = ( !empty($_REQUEST['mode']) ) ? $_REQUEST['mode'] : '';
$action    = ( !empty($_REQUEST['action']) ) ? $_REQUEST['action'] : '';
$page_id   = ( !empty($_REQUEST['page']) ) ? intval($_REQUEST['page']) : 1;
$sql_type  = ( !empty($_REQUEST['type']) ) ? trim($_REQUEST['type']) : '';
$sql_order = ( !empty($_REQUEST['order']) ) ? trim($_REQUEST['order']) : '';
$mode_ary  = array('liste', 'log', 'abonnes', 'download', 'iframe');

if( !in_array($mode, $mode_ary) )
{
	Location('index.php');
}

if( isset($_POST['cancel']) )
{
	Location('view.php?mode=' . $mode);
}

$vararray = array('purge', 'edit', 'delete');
foreach( $vararray AS $varname )
{
	if( isset($_POST[$varname]) )
	{
		$action = $varname;
	}
}

if( ( $mode != 'liste' || ( $mode == 'liste' && $action != 'add' ) ) && !$admindata['session_liste'] )
{
	$output->build_listbox(AUTH_VIEW);
}
else if( $admindata['session_liste'] )
{
	$listdata = $auth->listdata[$admindata['session_liste']];
}

$output->build_listbox(AUTH_VIEW, false, './view.php?mode=' . $mode);

//
// Mode download : t�l�chargement des fichiers joints � un log
//
if( $mode == 'download' )
{
	if( !$auth->check_auth(AUTH_VIEW, $admindata['session_liste']) )
	{
		trigger_error('Not_auth_view', MESSAGE);
	}
	
	include $waroot . 'includes/class.attach.php';
	
	$file_id = ( !empty($_GET['fid']) ) ? intval($_GET['fid']) : 0;
	$attach  = new Attach();
	$attach->download_file($file_id);
}

//
// Mode iframe pour visualisation des logs 
//
else if( $mode == 'iframe' )
{
	if( !$auth->check_auth(AUTH_VIEW, $admindata['session_liste']) )
	{
		$output->basic($lang['Message']['Not_auth_view']);
	}
	
	$log_id = ( !empty($_GET['id']) ) ? intval($_GET['id']) : 0;
	$format = ( isset($_GET['format']) ) ? $_GET['format'] : 0;
	
	if( $listdata['liste_format'] != FORMAT_MULTIPLE )
	{
		$format = $listdata['liste_format'];
	}
	
	$body_type = ( $format == FORMAT_HTML ) ? 'log_body_html' : 'log_body_text';
	
	$sql = "SELECT $body_type 
		FROM " . LOG_TABLE . " 
		WHERE log_id = $log_id AND liste_id = " . $listdata['liste_id'];
	if( !($result = $db->query($sql)) )
	{
		$output->basic('Impossible d\'obtenir les donn�es sur ce log');
	}
	
	if( $row = $db->fetch_array($result) )
	{
		$body = $row[$body_type];
		
		if( strlen($body) )
		{
			if( $format == FORMAT_HTML )
			{
				$body = preg_replace(
					'/<(.+?)"cid:([^\\:*\/?<">|]+)"([^>]*)?>/i',
					'<\\1"' . $waroot . 'options/show.php?file=\\2&amp;sessid=' . $session->session_id . '"\\3>',
					$body
				);
			}
			else
			{
				$body = nl2br(active_urls(htmlspecialchars(trim($body))));
				$body = preg_replace('/(\*\w+\*)/', '<strong>\\1</strong>', $body);
				$body = preg_replace('/(\/\w+\/)/', '<em>\\1</em>', $body);
				$body = preg_replace('/(_\w+_)/', '<u>\\1</u>', $body);
			}
			
			if( $format != FORMAT_HTML )
			{
				$body = str_replace('{LINKS}', '<a href="#" onclick="return false;">' . $listdata['form_url'] . '... (lien fictif)</a>', $body);
				$output->basic($body);
			}
			else
			{
				echo str_replace('{LINKS}', '<a href="#" onclick="return false;">' . $lang['Label_link'] . ' (lien fictif)</a>', $body);
			}
		}
		else
		{
			$output->basic($lang['Message']['log_not_exists']);
		}
	}
	else
	{
		$output->basic($lang['Message']['log_not_exists']);
	}
	
	exit;
}

//
// Mode gestion des abonn�s 
//
else if( $mode == 'abonnes' )
{
	switch( $action )
	{
		case 'delete':
			$auth_type = AUTH_DEL;
			break;
		
		case 'view':
		default:
			$auth_type = AUTH_VIEW;
			break;
	}
	
	if( !$auth->check_auth($auth_type, $admindata['session_liste']) )
	{
		trigger_error('Not_' . $auth->auth_ary[$auth_type], MESSAGE);
	}
	
	$abo_id     = ( !empty($_REQUEST['id']) ) ? intval($_REQUEST['id']) : 0;
	$get_string = '';
	
	//
	// Si la fonction de recherche est sollicit�e 
	//
	$abo_status     = ABO_ACTIF;
	$sql_search     = '';
	$search_keyword = ( !empty($_REQUEST['keyword']) ) ? trim($_REQUEST['keyword']) : '';
	$search_date    = ( !empty($_REQUEST['days']) ) ? intval($_REQUEST['days']) : 0;
	
	if( $search_keyword != '' || $search_date )
	{
		if( strlen($search_keyword) > 1 )
		{
			$get_string .= '&amp;keyword=' . urlencode($search_keyword);
			$sql_search .= ' AND a.abo_email LIKE \'' . $db->escape(str_replace('*', '%', urldecode($search_keyword))) . '\' ';
		}
		
		if( $search_date != 0 )
		{
			$get_string .= '&amp;days=' . $search_date;
			
			if( $search_date < 0 )
			{
				$abo_status = ABO_INACTIF;
			}
			else
			{
				$sql_search .= ' AND a.abo_register_date >= ' . (time() - ($search_date * 86400)) . ' ';
			}
		}
		
		if( isset($_POST['search']) )
		{
			$page_id = 1;
		}
	}
	
	//
	// Classement
	//
	if( $sql_type == 'abo_email' || $sql_type == 'abo_register_date' || $sql_type == 'format' )
	{
		$get_string .='&amp;type=' . $sql_type;
	}
	else
	{
		$sql_type = 'abo_register_date';
	}
	
	if( $sql_order == 'ASC' || $sql_order == 'DESC' )
	{
		$get_string .='&amp;order=' . $sql_order;
	}
	else
	{
		$sql_order = 'DESC';
	}
	
	$get_page = ( $page_id > 1 ) ? '&amp;page=' . $page_id : '';
	
	if( $action == 'view' )
	{
		if( !$abo_id )
		{
			$output->redirect('./view.php?mode=abonnes', 4);
			trigger_error('No_abo_id', MESSAGE);
		}
		
		$liste_id_ary = $auth->check_auth(AUTH_VIEW);
		
		$sql = "SELECT a.*, al.liste_id 
			FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al 
			WHERE a.abo_id = $abo_id 
				AND al.abo_id = a.abo_id 
				AND al.liste_id IN(" . implode(', ', $liste_id_ary) . ")";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les donn�es sur cet abonn�', ERROR);
		}
		
		if( $row = $db->fetch_array($result) )
		{
			$abodata = array();
			
			$abodata['abo_id']     = $row['abo_id'];
			$abodata['abo_email']  = $row['abo_email'];
			$abodata['abo_pseudo'] = $row['abo_pseudo'];
			$abodata['reg_date']   = $row['abo_register_date'];
			$abodata['listes']     = array();
			
			do
			{
				$abodata['listes'][$row['liste_id']] = $auth->listdata[$row['liste_id']]['liste_name'];
			}
			while( $row = $db->fetch_array($result) );
		}
		else
		{
			trigger_error('abo_not_exists', MESSAGE);
		}
		
		$s_title = ( !empty($abodata['abo_pseudo']) ) ? $abodata['abo_pseudo'] : $abodata['abo_email'];
		
		$output->addHiddenField('abo_id', $abodata['abo_id']);
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->page_header();
		
		$output->set_filenames(array(
			'body' => 'view_abo_profil_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE'                 => sprintf($lang['Title']['profile'], $s_title),
			'L_EXPLAIN'               => nl2br($lang['Explain']['abo']),
			'L_PSEUDO'                => $lang['Abo_pseudo'],
			'L_EMAIL'                 => $lang['Email_address'],
			'L_REGISTER_DATE'         => $lang['Susbcribed_date'],
			'L_LISTE_TO_REGISTER'     => $lang['Liste_to_register'],
			'L_DELETE_ACCOUNT_BUTTON' => $lang['Button']['del_account'],
			
			'RETURN_TO_BACK'          => sprintf($lang['Click_return_back'], '<a href="' . sessid('./view.php?mode=abonnes' . $get_string . $get_page) . '">', '</a>'),
			'S_ABO_PSEUDO'            => ( !empty($abodata['abo_pseudo']) ) ? $abodata['abo_pseudo'] : '<b>' . $lang['No_data'] . '</b>',
			'S_ABO_EMAIL'             => $abodata['abo_email'],
			'S_REGISTER_DATE'         => convert_time($nl_config['date_format'], $abodata['reg_date']),
			
			'S_HIDDEN_FIELDS'         => $output->getHiddenFields()
		));
		
		foreach( $abodata['listes'] AS $liste_id => $liste_name )
		{
			$output->assign_block_vars('listerow', array(
				'LISTE_NAME'   => $liste_name,
				'U_VIEW_LISTE' => sessid('./view.php?mode=abonnes&amp;liste=' . $liste_id)
			));
		}
		
		$output->pparse('body');
		
		$output->page_footer();
	}
	else if( $action == 'delete' )
	{
		$email_list = ( !empty($_POST['email_list']) ) ? $_POST['email_list'] : '';
		$abo_id_ary = ( !empty($_POST['abo_id']) ) ? $_POST['abo_id'] : array();
		
		if( !is_array($abo_id_ary) )
		{
			$abo_id_ary = array(intval($abo_id_ary));
		}
		
		if( $email_list == '' && count($abo_id_ary) == 0 )
		{
			$output->redirect('./view.php?mode=abonnes', 4);
			trigger_error('No_abo_id', MESSAGE);
		}
		
		if( isset($_POST['confirm']) )
		{
			$db->transaction(START_TRC);
			
			switch( DATABASE )
			{
				case 'postgre':
					$sql = "DELETE FROM " . ABONNES_TABLE . " 
						WHERE abo_id IN(
							SELECT abo_id 
							FROM " . ABO_LISTE_TABLE . " 
							WHERE abo_id IN(" . implode(', ', $abo_id_ary) . ") 
							GROUP BY abo_id 
							HAVING COUNT(abo_id) = 1
						)";
					if( !$db->query($sql) )
					{
						trigger_error('Impossible de supprimer les entr�es inutiles de la table des abonn�s', ERROR);
					}
					break;
				
				default:
					$sql = "SELECT abo_id 
						FROM " . ABO_LISTE_TABLE . " 
						WHERE abo_id IN(" . implode(', ', $abo_id_ary) . ") 
						GROUP BY abo_id 
						HAVING COUNT(abo_id) = 1";
					if( $result = $db->query($sql) )
					{
						if( $row = $db->fetch_array($result) )
						{
							$id_ary = array();
							
							do
							{
								$id_ary[] = $row['abo_id'];
							}
							while( $row = $db->fetch_array($result) );
							
							$sql = "DELETE FROM " . ABONNES_TABLE . " 
								WHERE abo_id IN(" . implode(', ', $id_ary) . ")";
							if( !$db->query($sql) )
							{
								trigger_error('Impossible de supprimer les entr�es inutiles de la table des abonn�s', ERROR);
							}
						}
					}
					break;
			}
			
			$sql = "DELETE FROM " . ABO_LISTE_TABLE . " 
				WHERE abo_id IN(" . implode(', ', $abo_id_ary) . ") 
					AND liste_id = " . $listdata['liste_id'];
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer les entr�es de la table abo_liste', ERROR);
			}
			
			$db->transaction(END_TRC);
			//
			// Optimisation des tables
			//
			$db->check(array(ABONNES_TABLE, ABO_LISTE_TABLE));
			
			$output->redirect('./view.php?mode=abonnes', 4);
			
			$message  = $lang['Message']['abo_deleted'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_abo'], '<a href="' . sessid('./view.php?mode=abonnes') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
		else
		{
			unset($abo_id);
			
			$output->addHiddenField('action', 'delete');
			$output->addHiddenField('sessid', $session->session_id);
			
			if( $email_list != '' )
			{
				$email_list   = array_map('trim', explode(',', $email_list));
				$total_emails = count($email_list);
				$sql_list     = '';
				
				for( $i = 0; $i < $total_emails; $i++ )
				{
					if( !empty($email_list[$i]) )
					{
						$sql_list .= ( $i == 0 ) ? '\'' . $email_list[$i] . '\'' : ', \'' . $email_list[$i] . '\'';
					}
				}
				
				if( $sql_list == '' )
				{
					$output->redirect('./view.php?mode=abonnes', 4);
					trigger_error('No_abo_id', MESSAGE);
				}
				
				$sql = "SELECT a.abo_id 
					FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al 
					WHERE al.liste_id = $listdata[liste_id]
						AND a.abo_id = al.abo_id 
						AND a.abo_email IN($sql_list)";
				if( !($result = $db->query($sql)) )
				{
					trigger_error('Impossible d\'obtenir les ID des adresses email entr�es', ERROR);
				}
				
				if( $db->num_rows($result) == 0 )
				{
					$output->redirect('./view.php?mode=abonnes', 4);
					trigger_error('No_abo_email', MESSAGE);
				}
				
				while( $row = $db->fetch_array($result) )
				{
					$output->addHiddenField('abo_id[]', $row['abo_id']);
				}
			}
			else
			{
				foreach( $abo_id_ary AS $abo_id )
				{
					$output->addHiddenField('abo_id[]', $abo_id);
				}
			}
			
			$output->page_header();
			
			$output->set_filenames(array(
				'body' => 'confirm_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_CONFIRM' => $lang['Title']['confirm'],
				
				'TEXTE' => $lang['Delete_abo'],
				'L_YES' => $lang['Yes'],
				'L_NO'  => $lang['No'],
				
				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM' => sessid('./view.php?mode=abonnes')
			));
			
			$output->pparse('body');
			
			$output->page_footer();
		}
	}
	
	$abo_per_page = 40;
	$start        = (($page_id - 1) * $abo_per_page);
	
	$sql = "SELECT COUNT(a.abo_id) AS total_abo
		FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al
		WHERE al.liste_id = $listdata[liste_id]
			AND a.abo_id = al.abo_id
			AND a.abo_status = " . $abo_status . $sql_search;
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir le nombre d\'abonn�s', ERROR);
	}
	
	$total_abo = $db->result($result, 0, 'total_abo');
	
	if( $total_abo > 0 )
	{
		$sql = "SELECT a.abo_id, a.abo_email, a.abo_register_date, al.format
			FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al
			WHERE al.liste_id = $listdata[liste_id]
				AND a.abo_id = al.abo_id
				AND a.abo_status = $abo_status
				$sql_search
			ORDER BY $sql_type " . $sql_order;
		if( !($result = $db->query($sql, $start, $abo_per_page)) )
		{
			trigger_error('Impossible d\'obtenir la liste des abonn�s', ERROR);
		}
		
		$aborow = $db->fetch_rowset($result);
		
		$total_pages = ceil($total_abo / $abo_per_page);
		if( $page_id > 1 )
		{
			$output->addLink(
				'first',
				'./view.php?mode=abonnes' . $get_string . '&amp;page=1',
				$lang['First_page']
			);
			
			$output->addLink(
				'prev',
				'./view.php?mode=abonnes' . $get_string . '&amp;page=' . ($page_id - 1),
				$lang['Prev_page']
			);
		}
		
		if( $page_id < $total_pages )
		{
			$output->addLink(
				'next',
				'./view.php?mode=abonnes' . $get_string . '&amp;page=' . ($page_id + 1),
				$lang['Next_page']
			);
			
			$output->addLink(
				'last',
				'./view.php?mode=abonnes' . $get_string . '&amp;page=' . $total_pages,
				$lang['Last_page']
			);
		}
	}
	else
	{
		$aborow = array();
	}
	
	$search_days_box  = '<select name="days">';
	$search_days_box .= '<option value="0"> - ' . $lang['All_abo'] . ' - </option>';
	
	$selected = ( $search_date == -1 ) ? ' selected="selected"' : '';
	$search_days_box .= '<option value="-1"' . $selected . '> - ' . $lang['Inactive_account'] . ' - </option>';
	
	for( $i = 0, $days = 10; $i < 4; $i++, $days *= 3 )
	{
		$selected = ( $search_date == $days ) ? ' selected="selected"' : '';
		$search_days_box .= '<option value="' . $days . '"' . $selected . '> - ' . sprintf($lang['Days_interval'], $days) . ' - </option>';
	}
	$search_days_box .= '</select>';
	
	$navigation = navigation(sessid('./view.php?mode=abonnes' . $get_string), $total_abo, $abo_per_page, $page_id);
	
	$output->addHiddenField('sessid', $session->session_id);
	
	$output->page_header();
	
	$output->set_filenames(array(
		'body' => 'view_abo_list_body.tpl'
	));
	
	$output->assign_vars(array(
		'L_EXPLAIN'        => nl2br($lang['Explain']['abo']),
		'L_TITLE'          => $lang['Title']['abo'],
		'L_SEARCH'         => $lang['Search_abo'],
		'L_SEARCH_NOTE'    => $lang['Search_abo_note'],
		'L_SEARCH_BUTTON'  => $lang['Button']['search'],
		'L_CLASSEMENT'     => $lang['Classement'],
		'L_BY_EMAIL'       => $lang['By_email'],
		'L_BY_DATE'        => $lang['By_date'],
		'L_BY_FORMAT'      => $lang['By_format'],
		'L_BY_ASC'         => $lang['By_asc'],
		'L_BY_DESC'        => $lang['By_desc'],
		'L_CLASSER_BUTTON' => $lang['Button']['classer'],
		'L_EMAIL'          => $lang['Email_address'],
		'L_DATE'           => $lang['Susbcribed_date'],
		
		'KEYWORD'              => htmlspecialchars($search_keyword),
		'SEARCH_DAYS_BOX'      => $search_days_box,
		'SELECTED_TYPE_EMAIL'  => ( $sql_type == 'abo_email' ) ? ' selected="selected"' : '',
		'SELECTED_TYPE_DATE'   => ( $sql_type == 'abo_register_date' ) ? ' selected="selected"' : '',
		'SELECTED_TYPE_FORMAT' => ( $sql_type == 'format' ) ? ' selected="selected"' : '',
		'SELECTED_ORDER_ASC'   => ( $sql_order == 'ASC' ) ? ' selected="selected"' : '',
		'SELECTED_ORDER_DESC'  => ( $sql_order == 'DESC' ) ? ' selected="selected"' : '',
		
		'PAGINATION'      => $navigation,
		'PAGEOF'          => ( $total_abo > 0 ) ? sprintf($lang['Page_of'], $page_id, ceil($total_abo / $abo_per_page)) : '',
		'NUM_SUBSCRIBERS' => ( $total_abo > 0 ) ? '[ <b>' . $total_abo . '</b> ' . $lang['Module']['subscribers'] . ' ]' : '',
		
		'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
		'U_FORM'          => sessid('./view.php?mode=abonnes' . $get_page)
	));
	
	if( $listdata['liste_format'] == FORMAT_MULTIPLE )
	{
		$output->assign_block_vars('view_format', array(
			'L_FORMAT' => $lang['Format']
		));
	}
	
	if( $num_abo = count($aborow) )
	{
		$display_checkbox = false;
		if( $auth->check_auth(AUTH_DEL, $listdata['liste_id']) )
		{
			$output->assign_block_vars('delete_option', array(
				'L_FAST_DELETION'      => $lang['Fast_deletion'],
				'L_FAST_DELETION_NOTE' => $lang['Fast_deletion_note'],
				'L_DELETE_BUTTON'      => $lang['Button']['delete'],
				'L_DELETE_ABO_BUTTON'  => $lang['Button']['del_abo']
			));
			
			$display_checkbox = true;
		}
		
		for( $i = 0; $i < $num_abo; $i++ )
		{
			$output->assign_block_vars('aborow', array(
				'TD_CLASS'          => ( !($i % 2) ) ? 'row1' : 'row2',
				'ABO_EMAIL'         => $aborow[$i]['abo_email'],
				'ABO_REGISTER_DATE' => convert_time($nl_config['date_format'], $aborow[$i]['abo_register_date']),
				'U_VIEW'            => sessid('./view.php?mode=abonnes&amp;action=view&amp;id=' . $aborow[$i]['abo_id'] . $get_string . $get_page)
			));
			
			if( $listdata['liste_format'] == FORMAT_MULTIPLE )
			{
				$output->assign_block_vars('aborow.format', array(
					'ABO_FORMAT' => ( $aborow[$i]['format'] == FORMAT_HTML ) ? 'html' : 'texte'
				));
			}
			
			if( $display_checkbox )
			{
				$output->assign_block_vars('aborow.delete', array(
					'ABO_ID' => $aborow[$i]['abo_id']
				));
			}
		}
	}
	else
	{
		if( isset($_POST['search']) )
		{
			$empty_reason = $lang['No_search_result'];
		}
		else
		{
			$empty_reason = $lang['No_abo_in_list'];
		}
		
		$output->assign_block_vars('empty', array(
			'L_EMPTY' => $empty_reason
		));
	}
}

//
// Mode Listes de diffusion
//
else if( $mode == 'liste' )
{
	switch( $action )
	{
		case 'add':
		case 'delete':
			if( $admindata['admin_level'] != ADMIN )
			{
				$output->redirect('./view.php?mode=liste', 4);
				
				$message  = $lang['Message']['Not_authorized'];
				$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
				trigger_error($message, MESSAGE);
			}
			
			$auth_type = false;
			break;
		
		case 'purge':
			$auth_type = AUTH_DEL;
			break;
		
		case 'edit':
			$auth_type = AUTH_EDIT;
			break;
		
		default:
			$auth_type = AUTH_VIEW;
			break;
	}
	
	if( $auth_type && !$auth->check_auth($auth_type, $admindata['session_liste']) )
	{
		trigger_error('Not_' . $auth->auth_ary[$auth_type], MESSAGE);
	}
	
	if( $action == 'add' || $action == 'edit' )
	{
		$vararray = array(
			'liste_name', 'sender_email', 'return_email', 'form_url', 'liste_sig', 
			'pop_host', 'pop_user', 'pop_pass', 'liste_alias'
		);
		foreach( $vararray AS $varname )
		{
			${$varname} = ( !empty($_POST[$varname]) ) ? trim($_POST[$varname]) : '';
		}
		
		$default_values = array(
			'liste_format'   => FORMAT_TEXTE,
			'limitevalidate' => 3,
			'purge_freq'     => 7,
			'pop_port'       => 110
		);
		
		$vararray2 = array(
			'liste_format', 'confirm_subscribe', 'limitevalidate', 'auto_purge', 'purge_freq', 'use_cron', 'pop_port'
		);
		foreach( $vararray2 AS $varname )
		{
			if( isset($_POST[$varname]) )
			{
				${$varname} = intval($_POST[$varname]);
			}
			else
			{
				${$varname} = ( isset($default_values[$varname]) ) ? $default_values[$varname] : 0;
			}
		}
		
		if( isset($_POST['submit']) )
		{
			$liste_name = strip_tags($liste_name);
			$liste_sig  = strip_tags($liste_sig);
			
			if( strlen($liste_name) < 3 || strlen($liste_name) > 30 )
			{
				$error = TRUE;
				$msg_error[] = $lang['Invalid_liste_name'];
			}
			
			if( !in_array($liste_format, array(FORMAT_TEXTE, FORMAT_HTML, FORMAT_MULTIPLE)) )
			{
				$error = TRUE;
				$msg_error[] = $lang['Unknown_format'];
			}
			
			include $waroot . 'includes/functions.validate.php';
			
			$result = check_email($sender_email);
			if( $result['error'] )
			{
				$error = TRUE;
				$msg_error[] = $result['message'];
			}
			
			if( $return_email != '' )
			{
				$result = check_email($return_email);
				
				if( $result['error'] )
				{
					$error = TRUE;
					$msg_error[] = $result['message'];
				}
			}
			
			if( $liste_alias != '' )
			{
				$result = check_email($liste_alias);
				
				if( $result['error'] )
				{
					$error = TRUE;
					$msg_error[] = $result['message'];
				}
			}
			
			if( $use_cron && !is_disabled_func('fsockopen') )
			{
				include $waroot . 'includes/class.pop.php';
				
				$pop = new Pop();
				
				$result = $pop->connect(
					$pop_host,
					$pop_port,
					$pop_user,
					$pop_pass
				);
				
				if( !$result )
				{
					$error = TRUE;
					$msg_error[] = sprintf(nl2br($lang['Message']['bad_pop_param']), htmlspecialchars($pop->msg_error));
				}
				else
				{
					$pop->quit();
				}
			}
			else
			{
				$use_cron = 0;
			}
			
			if( !$error )
			{
				$sql_data   = $sql_where = array();
				$liste_name = htmlspecialchars($liste_name);
				$vararray   = array_merge($vararray, $vararray2);
				
				foreach( $vararray AS $varname )
				{
					$sql_data[$varname] = ${$varname};
				}
				
				if( $action == 'add' )
				{
					$sql_type = 'INSERT';
					$sql_data['liste_startdate'] = time();
				}
				else
				{
					$sql_type = 'UPDATE';
					$sql_where['liste_id'] = $listdata['liste_id'];
				}
				
				if( !$db->query_build($sql_type, LISTE_TABLE, $sql_data, $sql_where) )
				{
					trigger_error('Impossible de mettre � jour la table des listes', ERROR);
				}
				
				if( $action == 'add' )
				{
					$new_liste_id = $db->next_id();
					
					$sql = "UPDATE " . SESSIONS_TABLE . " 
						SET session_liste = $new_liste_id 
						WHERE session_id = '{$session->session_id}' 
							AND admin_id = " . $admindata['admin_id'];
					if( !$db->query($sql) )
					{
						trigger_error('Impossible de mettre � jour le session_liste', ERROR);
					}
				}
				
				$output->redirect('./view.php?mode=liste', 4);
				
				$message  = ( $action == 'add' ) ? $lang['Message']['liste_created'] : $lang['Message']['liste_edited'];
				$message .= '<br /><br />' . sprintf($lang['Click_return_liste'], '<a href="' . sessid('./view.php?mode=liste') . '">', '</a>');
				trigger_error($message, MESSAGE);
			}
		}
		else if( $action == 'edit' )
		{
			$listdata['liste_name'] = unhtmlspecialchars($listdata['liste_name']);
			$vararray = array_merge($vararray, $vararray2);
			
			foreach( $vararray AS $varname )
			{
				${$varname} = $listdata[$varname];
			}
		}
		
		include $waroot . 'includes/functions.box.php';
		
		$output->addHiddenField('action', $action);
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->page_header();
		
		$output->set_filenames(array(
			'body' => 'edit_liste_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE'              => ( $action == 'add' ) ? $lang['Title']['add_liste'] : $lang['Title']['edit_liste'],
			'L_TITLE_PURGE'        => $lang['Title']['purge_sys'],
			'L_TITLE_CRON'         => $lang['Title']['cron'],
			'L_EXPLAIN'            => nl2br($lang['Explain']['liste']),
			'L_EXPLAIN_PURGE'      => nl2br($lang['Explain']['purge']),
			'L_EXPLAIN_CRON'       => nl2br(sprintf($lang['Explain']['cron'], '<a href="' . $waroot . 'docs/faq.' . $lang['CONTENT_LANG'] . '.html#6">', '</a>')),
			'L_LISTE_NAME'         => $lang['Liste_name'],
			'L_AUTH_FORMAT'        => $lang['Auth_format'],
			'L_SENDER_EMAIL'       => $lang['Sender_email'],
			'L_RETURN_EMAIL'       => $lang['Return_email'],
			'L_CONFIRM_SUBSCRIBE'  => $lang['Confirm_subscribe'],
			'L_LIMITEVALIDATE'     => $lang['Limite_validate'],
			'L_NOTE_VALIDATE'      => nl2br($lang['Note_validate']),
			'L_FORM_URL'           => $lang['Form_url'],
			'L_SIG_EMAIL'          => $lang['Sig_email'],
			'L_SIG_EMAIL_NOTE'     => nl2br($lang['Sig_email_note']),
			'L_DAYS'               => $lang['Days'],
			'L_YES'                => $lang['Yes'],
			'L_NO'                 => $lang['No'],
			'L_ENABLE_PURGE'       => $lang['Enable_purge'],
			'L_PURGE_FREQ'         => $lang['Purge_freq'],
			'L_USE_CRON'           => $lang['Use_cron'],
			'L_POP_SERVER'         => $lang['Pop_server'],
			'L_POP_PORT'           => $lang['Pop_port'],
			'L_POP_USER'           => $lang['Pop_user'],
			'L_POP_PASS'           => $lang['Pop_pass'],
			'L_LISTE_ALIAS'        => $lang['Liste_alias'],
			'L_POP_PORT_NOTE'      => nl2br($lang['Pop_port_note']),
			'L_VALID_BUTTON'       => $lang['Button']['valid'],
			'L_RESET_BUTTON'       => $lang['Button']['reset'],
			'L_CANCEL_BUTTON'      => $lang['Button']['cancel'],
			
			'LISTE_NAME'           => htmlspecialchars($liste_name),
			'FORMAT_BOX'           => format_box('liste_format', $liste_format, false, true),
			'SENDER_EMAIL'         => htmlspecialchars($sender_email),
			'RETURN_EMAIL'         => htmlspecialchars($return_email),			
			'FORM_URL'             => htmlspecialchars($form_url),
			'SIG_EMAIL'            => htmlspecialchars($liste_sig),
			'LIMITEVALIDATE'       => intval($limitevalidate),
			'PURGE_FREQ'           => intval($purge_freq),
			'CHECK_CONFIRM_YES'    => ( $confirm_subscribe ) ? ' checked="checked"' : '',
			'CHECK_CONFIRM_NO'     => ( !$confirm_subscribe ) ? ' checked="checked"' : '',
			'CHECKED_PURGE_ON'     => ( $auto_purge ) ? ' checked="checked"' : '',
			'CHECKED_PURGE_OFF'    => ( !$auto_purge ) ? ' checked="checked"' : '',
			'CHECKED_USE_CRON_ON'  => ( $use_cron ) ? ' checked="checked"' : '',
			'CHECKED_USE_CRON_OFF' => ( !$use_cron ) ? ' checked="checked"' : '',
			'DISABLED_CRON'        => ( is_disabled_func('fsockopen') ) ? ' disabled="disabled"' : '',
			'WARNING_CRON'         => ( is_disabled_func('fsockopen') ) ? ' <span style="color: red;">[not available]</span>' : '',
			'POP_HOST'             => htmlspecialchars($pop_host),
			'POP_PORT'             => intval($pop_port),
			'POP_USER'             => htmlspecialchars($pop_user),
			'POP_PASS'             => htmlspecialchars($pop_pass),
			'LISTE_ALIAS'          => htmlspecialchars($liste_alias),
			
			'S_HIDDEN_FIELDS'      => $output->getHiddenFields()
		));
		
		$output->pparse('body');
		
		$output->page_footer();
	}
	else if( $action == 'delete' )
	{
		if( isset($_POST['confirm']) )
		{
			$liste_id = ( !empty($_POST['liste_id']) ) ? intval($_POST['liste_id']) : 0;
			
			$db->transaction(START_TRC);
			
			$sql = "DELETE FROM " . AUTH_ADMIN_TABLE . " 
				WHERE liste_id = " . $listdata['liste_id'];
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer les entr�es de la table des permissions', ERROR);
			}
			
			if( isset($_POST['delete_all']) )
			{
				switch( DATABASE )
				{
					case 'postgre':
						$sql = "DELETE FROM " . ABONNES_TABLE . "
							WHERE abo_id IN(
								SELECT abo_id
								FROM " . ABO_LISTE_TABLE . "
								WHERE abo_id IN(
									SELECT abo_id
									FROM " . ABO_LISTE_TABLE . " AS al
									WHERE liste_id = $listdata[liste_id]
								)
								GROUP BY abo_id
								HAVING COUNT(abo_id) = 1
							)";
						if( !$db->query($sql) )
						{
							trigger_error('Impossible de supprimer les entr�es inutiles de la table des abonn�s', ERROR);
						}
						break;
					
					default:
						$sql = "SELECT abo_id 
							FROM " . ABO_LISTE_TABLE . " AS al 
							WHERE liste_id = " . $listdata['liste_id'];
						if( !($result = $db->query($sql)) )
						{
							trigger_error('Impossible d\'obtenir la liste des abonn�s de cette liste', ERROR);
						}
						
						if( $row = $db->fetch_array($result) )
						{
							$abo_id_ary = array();
							
							do
							{
								$abo_id_ary[] = $row['abo_id'];
							}
							while( $row = $db->fetch_array($result) );
							
							$sql = "SELECT abo_id 
								FROM " . ABO_LISTE_TABLE . " 
								WHERE abo_id IN(" . implode(', ', $abo_id_ary) . ") 
								GROUP BY abo_id 
								HAVING COUNT(abo_id) = 1";
							if( !($result = $db->query($sql)) )
							{
								trigger_error('Impossible d\'obtenir la liste des comptes � supprimer', ERROR);
							}
							
							if( $row = $db->fetch_array($result) )
							{
								$abo_id_ary = array();
								
								do
								{
									$abo_id_ary[] = $row['abo_id'];
								}
								while( $row = $db->fetch_array($result) );
								
								$sql = "DELETE FROM " . ABONNES_TABLE . " 
									WHERE abo_id IN(" . implode(', ', $abo_id_ary) . ")";
								if( !$db->query($sql) )
								{
									trigger_error('Impossible de supprimer les entr�es inutiles de la table des abonn�s', ERROR);
								}
							}
						}
						break;
				}
				
				$sql = "DELETE FROM " . ABO_LISTE_TABLE . " 
					WHERE liste_id = " . $listdata['liste_id'];
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de supprimer les entr�es de la table abo_liste', ERROR);
				}
				
				$sql = "SELECT log_id 
					FROM " . LOG_TABLE . " 
					WHERE liste_id = " . $listdata['liste_id'];
				if( !($result = $db->query($sql)) )
				{
					trigger_error('Impossible d\'obtenir la liste des logs', ERROR);
				}
				
				$log_id_ary = array();
				while( $row = $db->fetch_array($result) )
				{
					$log_id_ary[] = $row['log_id'];
				}
				
				include $waroot . 'includes/class.attach.php';
				
				$attach = new Attach();
				$attach->delete_joined_files(true, $log_id_ary);
				
				$sql = "DELETE FROM " . LOG_TABLE . " 
					WHERE liste_id = " . $listdata['liste_id'];
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de supprimer les entr�es de la table des logs', ERROR);
				}
				
				include $waroot . 'includes/functions.stats.php';
				remove_stats($listdata['liste_id']);
			}
			else
			{
				if( !isset($auth->listdata[$liste_id]) )
				{
					trigger_error('No_liste_id', ERROR);
				}
				
				switch( DATABASE )
				{
					case 'postgre':
						$sql = "DELETE FROM " . ABO_LISTE_TABLE . "
							WHERE abo_id IN(
								SELECT abo_id
								FROM " . ABO_LISTE_TABLE . "
								WHERE abo_id IN(
									SELECT abo_id
									FROM " . ABO_LISTE_TABLE . "
									WHERE liste_id = $listdata[liste_id]
								) AND liste_id = $liste_id
							) AND liste_id = " . $listdata['liste_id'];
						if( !$db->query($sql) )
						{
							trigger_error('Impossible de supprimer les entr�es inutiles de la table abo_liste', ERROR);
						}
						
						$sql = "UPDATE " . ABO_LISTE_TABLE . "
							SET liste_id = $liste_id
							WHERE abo_id IN(
								SELECT abo_id
								FROM " . ABO_LISTE_TABLE . "
								WHERE abo_id IN(
									SELECT abo_id
									FROM " . ABO_LISTE_TABLE . "
									WHERE liste_id = $listdata[liste_id]
								) AND liste_id <> $liste_id
							) AND liste_id = " . $listdata['liste_id'];
						if( !$db->query($sql) )
						{
							trigger_error('Impossible de mettre � jour la table abo_liste', ERROR);
						}						
						break;
					
					default:
						$sql = "SELECT abo_id 
							FROM " . ABO_LISTE_TABLE . " 
							WHERE liste_id = " . $listdata['liste_id'];
						if( !($result = $db->query($sql)) )
						{
							trigger_error('Impossible d\'obtenir la liste des abonn�s', ERROR);
						}
						
						if( $row = $db->fetch_array($result) )
						{
							$abo_id_ary = array();
							
							do
							{
								$abo_id_ary[] = $row['abo_id'];
							}
							while( $row = $db->fetch_array($result) );
							
							$sql = "SELECT abo_id 
								FROM " . ABO_LISTE_TABLE . " 
								WHERE abo_id IN(" . implode(', ', $abo_id_ary) . ") 
									AND liste_id = " . $liste_id;
							if( !($result = $db->query($sql)) )
							{
								trigger_error('Impossible d\'obtenir la liste des abonn�s[2]', ERROR);
							}
							
							if( $row = $db->fetch_array($result) )
							{
								$abo_id_ary2 = array();
								
								do
								{
									$abo_id_ary2[] = $row['abo_id'];
								}
								while( $row = $db->fetch_array($result) );
								
								$sql = "DELETE FROM " . ABO_LISTE_TABLE . " 
									WHERE abo_id IN(" . implode(', ', $abo_id_ary2) . ") 
										AND liste_id = " . $listdata['liste_id'];
								if( !$db->query($sql) )
								{
									trigger_error('Impossible de supprimer les entr�es inutiles de la table abo_liste', ERROR);
								}
								
								$abo_id_ary = array_diff($abo_id_ary, $abo_id_ary2);
							}
							
							$sql = "UPDATE " . ABO_LISTE_TABLE . " 
								SET liste_id = $liste_id 
								WHERE abo_id IN(" . implode(', ', $abo_id_ary) . ") 
									AND liste_id = " . $listdata['liste_id'];
							if( !$db->query($sql) )
							{
								trigger_error('Impossible de mettre � jour la table abo_liste', ERROR);
							}
						}
						break;
				}
				
				$sql = "UPDATE " . LOG_TABLE . " 
					SET liste_id = $liste_id 
					WHERE liste_id = " . $listdata['liste_id'];
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de supprimer les entr�es de la table des logs', ERROR);
				}
				
				include $waroot . 'includes/functions.stats.php';
				remove_stats($listdata['liste_id'], $liste_id);
			}
			
			$sql = "DELETE FROM " . LISTE_TABLE . " 
				WHERE liste_id = " . $listdata['liste_id'];
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer l\'entr�e de la table des listes', ERROR);
			}
			
			$db->transaction(END_TRC);
			
			//
			// Optimisation des tables
			//
			$db->check(array(ABONNES_TABLE, ABO_LISTE_TABLE, LOG_TABLE, LOG_FILES_TABLE, JOINED_FILES_TABLE, LISTE_TABLE));
			
			$output->redirect('./index.php', 4);
			
			$message  = ( isset($_POST['delete_all']) ) ? $lang['Message']['Liste_del_all'] : nl2br($lang['Message']['Liste_del_move']);
			$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
		else
		{
			$list_box     = '';
			$liste_id_ary = $auth->check_auth(AUTH_VIEW);
			
			foreach( $auth->listdata AS $liste_id => $data )
			{
				if( in_array($liste_id, $liste_id_ary) && $liste_id != $listdata['liste_id'] )
				{
					$selected  = ( $admindata['session_liste'] == $liste_id ) ? ' selected="selected"' : '';
					$list_box .= '<option value="' . $liste_id . '"' . $selected . '> - ' . cut_str($data['liste_name'], 30) . ' - </option>';
				}
			}
			
			if( $list_box != '' )
			{
				$message  = $lang['Move_abo_logs'];
				$message .= '<br /><br />' . $lang['Move_to_liste'] . ' <select id="liste_id" name="liste_id">' . $list_box . '</select>';
				$message .= '<br /><br /><input type="checkbox" id="delete_all" name="delete_all" value="1" /> <label for="delete_all">' . $lang['Delete_abo_logs'] . '</label>';
			}
			else
			{
				$output->addHiddenField('delete_all', '1');
				$message = $lang['Delete_all'];
			}
			
			$output->addHiddenField('action', 'delete');
			$output->addHiddenField('sessid', $session->session_id);
			
			$output->page_header();
			
			$output->set_filenames(array(
				'body' => 'confirm_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_CONFIRM' => $lang['Title']['confirm'],
				
				'TEXTE' => $message,
				'L_YES' => $lang['Yes'],
				'L_NO'	=> $lang['No'],
				
				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM' => sessid('./view.php?mode=liste')
			));
			
			$output->pparse('body');
			
			$output->page_footer();
		}
	}
	else if( $action == 'purge' )
	{
		$abo_deleted = purge_liste($listdata['liste_id'], $listdata['limitevalidate'], $listdata['purge_freq']);
		
		$output->redirect('./view.php?mode=liste', 4);
		
		$message  = sprintf($lang['Message']['Success_purge'], $abo_deleted);
		$message .= '<br /><br />' . sprintf($lang['Click_return_liste'], '<a href="' . sessid('./view.php?mode=liste') . '">', '</a>');
		trigger_error($message, MESSAGE); 
	}
	
	$data = get_data($listdata['liste_id']);
	
	$num_inscrits = $data['num_inscrits'];
	$num_temp     = $data['num_temp'];
	$num_logs     = $data['num_logs'];
	$last_log     = $data['last_log'];
	
	switch( $listdata['liste_format'] )
	{
		case FORMAT_TEXTE:
			$l_format = 'txt';
			break;
		
		case FORMAT_HTML:
			$l_format = 'html';
			break;
		
		case FORMAT_MULTIPLE:
			$l_format = 'txt &amp; html';
			break;
		
		default:
			$l_format = $lang['Unknown'];
			break;
	}
		
	$output->page_header();
	
	$output->set_filenames( array(
		'body' => 'view_liste_body.tpl'
	));
	
	$output->assign_vars( array(
		'L_TITLE'             => $lang['Title']['info_liste'],
		'L_EXPLAIN'           => nl2br($lang['Explain']['liste']),
		'L_LISTE_ID'          => $lang['ID_list'],
		'L_LISTE_NAME'        => $lang['Liste_name'],
		'L_AUTH_FORMAT'       => $lang['Auth_format'],
		'L_SENDER_EMAIL'      => $lang['Sender_email'],
		'L_RETURN_EMAIL'      => $lang['Return_email'],
		'L_CONFIRM_SUBSCRIBE' => $lang['Confirm_subscribe'],
		'L_NUM_SUBSCRIBERS'   => $lang['Reg_subscribers_list'],
		'L_NUM_LOGS'          => $lang['Total_newsletter_list'],
		'L_FORM_URL'          => $lang['Form_url'],
		'L_STARTDATE'         => $lang['Liste_startdate'],
		
		'LISTE_ID'            => $listdata['liste_id'],
		'LISTE_NAME'          => $listdata['liste_name'],
		'AUTH_FORMAT'         => $l_format,
		'SENDER_EMAIL'        => $listdata['sender_email'],
		'RETURN_EMAIL'        => $listdata['return_email'],
		'CONFIRM_SUBSCRIBE'   => ( $listdata['confirm_subscribe'] ) ? $lang['Yes'] : $lang['No'], 
		'NUM_SUBSCRIBERS'     => $num_inscrits,
		'NUM_LOGS'            => $num_logs,
		'FORM_URL'            => htmlspecialchars($listdata['form_url']),
		'STARTDATE'           => convert_time($nl_config['date_format'], $listdata['liste_startdate'])
	));
	
	if( $listdata['confirm_subscribe'] )
	{
		$output->assign_block_vars('liste_confirm', array(
			'L_LIMITEVALIDATE' => $lang['Limite_validate'],
			'L_NUM_TEMP'       => $lang['Tmp_subscribers_list'],
			'L_DAYS'           => $lang['Days'],
			
			'LIMITEVALIDATE'   => $listdata['limitevalidate'],
			'NUM_TEMP'         => $num_temp
		));
	}
	
	if( $num_logs )
	{
		$output->assign_block_vars('date_last_log', array(
			'L_LAST_LOG' => $lang['Last_newsletter2'],
			'LAST_LOG'   => convert_time($nl_config['date_format'], $last_log)
		));
	}
	
	if( $auth->check_auth(AUTH_DEL, $listdata['liste_id']) || $auth->check_auth(AUTH_EDIT, $listdata['liste_id']) )
	{
		$output->assign_block_vars('admin_options', array());
		
		if( $admindata['admin_level'] == ADMIN )
		{
			$output->assign_block_vars('admin_options.auth_add', array(
				'L_ADD_LISTE' => $lang['Create_liste']
			));
			
			$output->assign_block_vars('admin_options.auth_del', array(
				'L_DELETE_LISTE' => $lang['Delete_liste']
			));
		}
		
		if( $auth->check_auth(AUTH_EDIT, $listdata['liste_id']) )
		{
			$output->assign_block_vars('admin_options.auth_edit', array(				
				'L_EDIT_LISTE' => $lang['Edit_liste']
			));
		}
		
		if( $auth->check_auth(AUTH_DEL, $listdata['liste_id']) )
		{
			$output->addHiddenField('sessid', $session->session_id);
			
			$output->assign_block_vars('purge_option', array(
				'L_PURGE_BUTTON'  => $lang['Button']['purge'],
				'S_HIDDEN_FIELDS' => $output->getHiddenFields()
			));
		}
	}
}

//
// Mode Gestion des logs/archives
//
else if( $mode == 'log' )
{
	switch( $action )
	{
		case 'delete':
			$auth_type = AUTH_DEL;
			break;
		
		default:
			$auth_type = AUTH_VIEW;
			break;
	}
	
	if( !$auth->check_auth($auth_type, $listdata['liste_id']) )
	{
		trigger_error('Not_' . $auth->auth_ary[$auth_type], MESSAGE);
	}
	
	$log_id = ( !empty($_GET['id']) ) ? intval($_GET['id']) : 0;
	
	if( $action == 'delete' )
	{
		$log_id_ary = ( !empty($_POST['log_id']) && is_array($_POST['log_id']) ) ? array_map('intval', $_POST['log_id']) : array();
		
		if( count($log_id_ary) == 0 )
		{
			$output->redirect('./view.php?mode=log', 4);
			trigger_error('No_log_id', MESSAGE);
		}
		
		if( isset($_POST['confirm']) )
		{
			$db->transaction(START_TRC);
			
			$sql = "DELETE FROM " . LOG_TABLE . " 
				WHERE log_id IN(" . implode(', ', $log_id_ary) . ")";
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer les logs', ERROR);
			}
			
			include $waroot . 'includes/class.attach.php';
			
			$attach = new Attach();
			$attach->delete_joined_files(true, $log_id_ary);
			
			$db->transaction(END_TRC);
			
			//
			// Optimisation des tables
			//
			$db->check(array(LOG_TABLE, LOG_FILES_TABLE, JOINED_FILES_TABLE));
			
			$output->redirect('./view.php?mode=log', 4);
			
			$message  = $lang['Message']['logs_deleted'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_logs'], '<a href="' . sessid('./view.php?mode=log') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
		else
		{
			unset($log_id);
			
			$output->addHiddenField('action', 'delete');
			$output->addHiddenField('sessid', $session->session_id);
			
			foreach( $log_id_ary AS $log_id )
			{
				$output->addHiddenField('log_id[]', $log_id);
			}
			
			$output->page_header();
			
			$output->set_filenames(array(
				'body' => 'confirm_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_CONFIRM' => $lang['Title']['confirm'],
				
				'TEXTE' => $lang['Delete_logs'],
				'L_YES' => $lang['Yes'],
				'L_NO'  => $lang['No'],
				
				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM' => sessid('./view.php?mode=log')
			));
			
			$output->pparse('body');
			
			$output->page_footer();
		}
	}
	
	$get_string = '';
	
	//
	// Classement 
	//
	if( $sql_type == 'log_subject' || $sql_type == 'log_date' )
	{
		$get_string .= '&amp;type=' . $sql_type;
	}
	else
	{
		$sql_type = 'log_date';
	}
	
	if( $sql_order == 'ASC' || $sql_order == 'DESC' )
	{
		$get_string .= '&amp;order=' . $sql_order;
	}
	else
	{
		$sql_order = 'DESC';
	}
	
	$log_per_page = 20;
	$start        = (($page_id - 1) * $log_per_page);
	
	$sql = "SELECT COUNT(log_id) AS total_logs 
		FROM " . LOG_TABLE . " 
		WHERE log_status = " . STATUS_SENDED . " 
			AND liste_id = " . $listdata['liste_id'];
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir le nombre de logs', ERROR);
	}
	
	$total_logs = $db->result($result, 0, 'total_logs');
	
	$logdata  = '';
	$logrow   = array();
	$num_logs = 0;
	
	if( $total_logs )
	{
		$sql = "SELECT log_id, log_subject, log_date, log_body_text, log_body_html, log_numdest 
			FROM " . LOG_TABLE . " 
			WHERE log_status = " . STATUS_SENDED . " 
				AND liste_id = $listdata[liste_id]
			ORDER BY $sql_type " . $sql_order;
		if( !($result = $db->query($sql, $start, $log_per_page)) )
		{
			trigger_error('Impossible d\'obtenir la liste des logs', ERROR);
		}
		
		while( $row = $db->fetch_array($result) )
		{
			if( $action == 'view' && $log_id == $row['log_id'] )
			{
				$logdata = $row;
				$logdata['joined_files'] = array();
			}
			
			$logrow[] = $row;
		}
		
		$sql = "SELECT COUNT(jf.file_id) as num_files, l.log_id
			FROM " . JOINED_FILES_TABLE . " AS jf, " . LOG_FILES_TABLE . " AS lf, " . LOG_TABLE . " AS l
			WHERE lf.log_id = l.log_id
				AND jf.file_id = lf.file_id
				AND l.liste_id = $listdata[liste_id]
			GROUP BY l.log_id";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les nombres de fichiers joints par log', ERROR);
		}
		
		$files_count = array();
		while( $row = $db->fetch_array($result) )
		{
			$files_count[$row['log_id']] = $row['num_files'];
		}
		
		$total_pages = ceil($total_logs / $log_per_page);
		if( $page_id > 1 )
		{
			$output->addLink(
				'first',
				'./view.php?mode=log' . $get_string . '&amp;page=1',
				$lang['First_page']
			);
			
			$output->addLink(
				'prev',
				'./view.php?mode=log' . $get_string . '&amp;page=' . ($page_id - 1),
				$lang['Prev_page']
			);
		}
		
		if( $page_id < $total_pages )
		{
			$output->addLink(
				'next',
				'./view.php?mode=log' . $get_string . '&amp;page=' . ($page_id + 1),
				$lang['Next_page']
			);
			
			$output->addLink(
				'last',
				'./view.php?mode=log' . $get_string . '&amp;page=' . $total_pages,
				$lang['Last_page']
			);
		}
		
		if( is_array($logdata) && !empty($files_count[$log_id]) )
		{
			$sql = "SELECT jf.file_id, jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype
				FROM " . JOINED_FILES_TABLE . " AS jf, " . LOG_FILES_TABLE . " AS lf, " . LOG_TABLE . " AS l
				WHERE l.log_id = $log_id
					AND lf.log_id = l.log_id
					AND jf.file_id = lf.file_id
					AND l.liste_id = $listdata[liste_id]
				ORDER BY jf.file_real_name ASC";
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir la liste des fichiers joints au log', ERROR);
			}
			
			$logdata['joined_files'] = $db->fetch_rowset($result);
		}
	}
	
	$navigation = navigation(sessid('./view.php?mode=log' . $get_string), $total_logs, $log_per_page, $page_id);
	
	$u_form	 = './view.php?mode=log' . ( ( $log_id > 0 ) ? '&amp;id=' . $log_id : '' );
	$u_form .= ( $action != '' ) ? '&amp;action=' . $action : '';
	$u_form .= ( $page_id > 1 ) ? '&amp;page=' . $page_id : '';
	
	$get_string .= ( $page_id > 1 ) ? '&amp;page=' . $page_id : '';
	
	$output->addHiddenField('sessid', $session->session_id);
	
	$output->page_header();
	
	$output->set_filenames(array(
		'body' => 'view_logs_body.tpl'
	));
	
	$output->assign_vars(array(
		'L_EXPLAIN'             => nl2br($lang['Explain']['logs']),
		'L_TITLE'               => $lang['Title']['logs'],
		'L_CLASSEMENT'          => $lang['Classement'],
		'L_BY_SUBJECT'          => $lang['By_subject'],
		'L_BY_DATE'             => $lang['By_date'],
		'L_BY_ASC'              => $lang['By_asc'],
		'L_BY_DESC'             => $lang['By_desc'],
		'L_CLASSER_BUTTON'      => $lang['Button']['classer'],
		'L_SUBJECT'             => $lang['Log_subject'],
		'L_DATE'                => $lang['Log_date'],
		
		'SELECTED_TYPE_SUBJECT' => ( $sql_type == 'log_subject' ) ? ' selected="selected"' : '',
		'SELECTED_TYPE_DATE'    => ( $sql_type == 'log_date' ) ? ' selected="selected"' : '',
		'SELECTED_ORDER_ASC'    => ( $sql_order == 'ASC' ) ? ' selected="selected"' : '',
		'SELECTED_ORDER_DESC'   => ( $sql_order == 'DESC' ) ? ' selected="selected"' : '',
		
		'PAGINATION'            => $navigation,
		'PAGEOF'                => ( $total_logs > 0 ) ? sprintf($lang['Page_of'], $page_id, ceil($total_logs / $log_per_page)) : '',
		'NUM_LOGS'              => ( $total_logs > 0 ) ? '[ <b>' . $total_logs . '</b> ' . $lang['Module']['log'] . ' ]' : '',
		
		'WAROOT'                => $waroot,
		'S_SESSID'              => $session->session_id,
		'S_HIDDEN_FIELDS'       => $output->getHiddenFields(),
		'U_FORM'                => sessid($u_form)
	));
	
	if( $num_logs = count($logrow) )
	{
		$display_checkbox = false;
		if( $auth->check_auth(AUTH_DEL, $listdata['liste_id']) )
		{
			$output->assign_block_vars('delete_option', array(
				'L_DELETE' => $lang['Button']['del_logs']
			));
			
			$display_checkbox = true;
		}
		
		for( $i = 0; $i < $num_logs; $i++ )
		{
			if( !empty($files_count[$logrow[$i]['log_id']]) )
			{
				if( $files_count[$logrow[$i]['log_id']] > 1 )
				{
					$s_title_clip = sprintf($lang['Joined_files'], $files_count[$logrow[$i]['log_id']]);
				}
				else
				{
					$s_title_clip = $lang['Joined_file'];
				}
				
				$s_clip = '<img src="' . $waroot . 'images/icon_clip.gif" width="10" height="13" alt="@" title="' . $s_title_clip . '" />';
			}
			else
			{
				$s_clip = '&nbsp;&nbsp;';
			}
			
			$output->assign_block_vars('logrow', array(
				'TD_CLASS'    => ( !($i % 2) ) ? 'row1' : 'row2',
				'ITEM_CLIP'   => $s_clip,
				'LOG_SUBJECT' => htmlspecialchars(cut_str($logrow[$i]['log_subject'], 60)),
				'LOG_DATE'    => convert_time($nl_config['date_format'], $logrow[$i]['log_date']),
				'U_VIEW'      => sessid('./view.php?mode=log&amp;action=view&amp;id=' . $logrow[$i]['log_id'] . $get_string)
			));
			
			if( $display_checkbox )
			{
				$output->assign_block_vars('logrow.delete', array(
					'LOG_ID' => $logrow[$i]['log_id']
				));
			}
		}
		
		if( $action == 'view' && is_array($logdata) )
		{
			$format = ( !empty($_POST['format']) ) ? intval($_POST['format']) : 0;
			
			$output->set_filenames(array(
				'iframe_body' => 'iframe_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_SUBJECT'  => $lang['Log_subject'],
				'L_NUMDEST'  => $lang['Log_numdest'],
				
				'SUBJECT'    => htmlspecialchars($logdata['log_subject']),
				'S_NUMDEST'  => $logdata['log_numdest'],
				'S_CODEBASE' => $nl_config['urlsite'] . $nl_config['path'] . 'admin/',
				'U_FRAME'    => sessid('./view.php?mode=iframe&amp;id=' . $log_id . '&amp;format=' . $format)
			));
			
			if( $listdata['liste_format'] == FORMAT_MULTIPLE )
			{
				include $waroot . 'includes/functions.box.php';
				
				$output->assign_block_vars('format_box', array(
					'L_FORMAT'    => $lang['Format'],
					'L_GO_BUTTON' => $lang['Button']['go'],
					'FORMAT_BOX'  => format_box('format', $format, true)
				));
			}
			
			$output->files_list($logdata, $format);
			$output->assign_var_from_handle('IFRAME', 'iframe_body');
		}
	}
	else
	{
		$output->assign_block_vars('empty', array(
			'L_EMPTY' => $lang['No_log_sended']
		));
	}
}

$output->pparse('body');

$output->page_footer();
?>