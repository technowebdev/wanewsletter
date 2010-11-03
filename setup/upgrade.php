<?php
/**
 * Copyright (c) 2002-2010 Aur�lien Maille
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

define('IN_UPGRADE', true);

require './setup.inc.php';

$admin_login = ( !empty($_POST['admin_login']) ) ? trim($_POST['admin_login']) : '';
$admin_pass  = ( !empty($_POST['admin_pass']) ) ? trim($_POST['admin_pass']) : '';

//
// Compatibilit� avec les versions < 2.1.2
//
if( !defined('NL_INSTALLED') && isset($dbhost) && !isset($_REQUEST['dbhost']) )
{
	define('NL_INSTALLED', true);
}

if( !defined('NL_INSTALLED') )
{
	plain_error("Wanewsletter ne semble pas install�");
}

$db = WaDatabase($dsn);

if( !$db->isConnected() )
{
	plain_error(sprintf($lang['Connect_db_error'], $db->error));
}

//
// R�cup�ration de la configuration
//
$sql = "SELECT * FROM " . CONFIG_TABLE;
if( !($result = $db->query($sql)) )
{
	plain_error("Impossible d'obtenir la configuration du script :\n" . $db->error);
}

$old_config = array();
while( $row = $result->fetch() )
{
	if( !isset($row['nom']) )
	{
		$old_config = $row;
		break;
	}
	else // branche 2.0
	{
		$old_config[$row['nom']] = $row['valeur'];
	}
}

//
// Compatibilit� avec les versions < 2.3
//
if( !defined('WA_VERSION') )
{
	define('WA_VERSION', $old_config['version']);
}

if( file_exists(WA_ROOTDIR . '/language/lang_' . $old_config['language'] . '.php') )
{
	require WA_ROOTDIR . '/language/lang_' . $old_config['language'] . '.php';
}

if( !preg_match('/^(2\.[0-4])[-.0-9a-zA-Z]+$/', WA_VERSION, $match) )
{
	message($lang['Unknown_version']);
}

define('WA_BRANCHE', $match[1]);

$output->set_filenames( array(
	'body' => 'upgrade.tpl'
));

$output->send_headers();

$output->assign_vars( array(
	'PAGE_TITLE'   => $lang['Title']['upgrade'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR'],
	'NEW_VERSION'  => WA_NEW_VERSION,
	'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : ''
));

if( $start )
{
	if( WA_BRANCHE == '2.0' || WA_BRANCHE == '2.1' )
	{
		if( WA_BRANCHE == '2.1' )
		{
			$field_level = 'level';
		}
		else
		{
			$field_level = 'droits';
		}
		
		$sql = "SELECT COUNT(*)
			FROM " . ADMIN_TABLE . "
			WHERE LOWER(user) = '" . $db->escape(strtolower($admin_login)) . "'
				AND passwd = '" . md5($admin_pass) . "'
				AND $field_level >= " . ADMIN;
	}
	else if( WA_BRANCHE == '2.2' || WA_BRANCHE == '2.3' || WA_BRANCHE == '2.4' )
	{
		$sql = "SELECT COUNT(*)
			FROM " . ADMIN_TABLE . "
			WHERE LOWER(admin_login) = '" . $db->escape(strtolower($admin_login)) . "'
				AND admin_pwd = '" . md5($admin_pass) . "'
				AND admin_level >= " . ADMIN;
	}
	
	$res = $db->query($sql);
	if( $res->column(0) == 0 )
	{
		$error = true;
		$msg_error[] = $lang['Message']['Error_login'];
	}
	
	$sql_create = SCHEMAS_DIR . '/' . $supported_db[$infos['driver']]['prefixe_file'] . '_tables.sql';
	
	if( !is_readable($sql_create) )
	{
		$error = true;
		$msg_error[] = $lang['Message']['sql_file_not_readable'];
	}
	
	if( !$error )
	{
		//
		// Lancement de la mise � jour
		// On allonge le temps maximum d'execution du script.
		//
		@set_time_limit(1200);
		
		$sql_create = parseSQL(file_get_contents($sql_create), $prefixe);
		
		foreach( $sql_create as $query )
		{
			preg_match('/CREATE TABLE ' . $prefixe . '([[:alnum:]_-]+)/i', $query, $match);
			
			$sql_create[$match[1]] = $query;
		}
		
		//
		// Nous v�rifions tout d'abord si des doublons sont pr�sents dans
		// la table des abonn�s.
		// Si des doublons sont pr�sents, la mise � jour ne peut continuer.
		//
		$fieldname = ( WA_BRANCHE == '2.0' || WA_BRANCHE == '2.1' ) ? 'email' : 'abo_email';
		
		$sql = "SELECT $fieldname
			FROM " . ABONNES_TABLE . "
			GROUP BY $fieldname
			HAVING COUNT($fieldname) > 1";
		if( !($result = $db->query($sql)) )
		{
			sql_error();
		}
		
		if( $row = $result->fetch() )
		{
			$emails = array();
			
			do
			{
				array_push($emails, $row[$fieldname]);
			}
			while( $row = $result->fetch() );
			
			message("Des adresses email sont pr�sentes en plusieurs exemplaires dans la table " . ABONNES_TABLE . ", la mise � jour ne peut continuer.
			Supprimez les doublons en cause puis relancez la mise � jour.
			Adresses email pr�sentes en plusieurs exemplaires : " . implode(', ', $emails));
		}
		
		if( WA_BRANCHE == '2.0' || WA_BRANCHE == '2.1' )
		{
			//
			// Reconstruction de la table de configuration
			//
			$old_config['engine_send']   = ( !empty($old_config['engine_send']) ) ? $old_config['engine_send'] : ENGINE_BCC;
			$old_config['emails_sended'] = ( !empty($old_config['emails_sended']) ) ? $old_config['emails_sended'] : 0;
			$old_config['date_format']   = ( !empty($old_config['date_format']) ) ? $old_config['date_format'] : 'j F Y H:i';
			$old_config['sender_email']  = ( !empty($old_config['sender_email']) ) ? $old_config['sender_email'] : $old_config['emailadmin'];
			$old_config['return_email']  = ( !empty($old_config['return_path_email']) ) ? $old_config['return_path_email'] : '';
			$old_config['signature']     = strip_tags($old_config['signature']);
			$old_config['auto_purge']    = ( !empty($old_config['use_auto_purge']) ) ? $old_config['use_auto_purge'] : 0;
			$old_config['purge_freq']    = ( !empty($old_config['purge_freq']) ) ? $old_config['purge_freq'] : 0;
			$old_config['purge_next']    = ( !empty($old_config['purge_next']) ) ? $old_config['purge_next'] : 0;
			
			$sql_update   = array();
			$sql_update[] = "DROP TABLE " . CONFIG_TABLE;
			$sql_update[] = $sql_create['config'];
			
			$startdate = 0;
			
			$sql = "SELECT MIN(date) FROM " . ABONNES_TABLE;
			if( $result = $db->query($sql) )
			{
				$startdate = $result->column(0);
			}
			
			if( !$startdate )
			{
				$startdate = time();
			}
			
			$sql_update[] = "INSERT INTO " . CONFIG_TABLE . " (sitename, urlsite, path, date_format, session_length, language, cookie_name, cookie_path, upload_path, max_filesize, engine_send, emails_sended, use_smtp, smtp_host, smtp_port, smtp_user, smtp_pass, gd_img_type, mailing_startdate)
				VALUES('" . $db->escape($old_config['sitename']) . "', '" . $db->escape($old_config['urlsite']) . "', '" . $db->escape($old_config['path']) . "', '" . $db->escape($old_config['date_format']) . "', " . $old_config['session_duree'] . ", '" . $old_config['language'] . "', 'wanewsletter', '/', 'admin/upload/', 80000, " . $old_config['engine_send'] . ", " . $old_config['emails_sended'] . ", " . $old_config['use_smtp'] . ", '" . $db->escape($old_config['smtp_host']) . "', '" . $old_config['smtp_port'] . "', '" . $db->escape($old_config['smtp_user']) . "', '" . $db->escape($old_config['smtp_pass']) . "', 'png', $startdate)";
			
			exec_queries($sql_update, true);
			
			//
			// Modif table session + ajout �ventuel table ban_list + cr�ation des
			// nouvelles tables de la version 2.2
			//
			$sql_update = array();
			
			switch( WA_VERSION )
			{
				case '2.0Beta':
				case '2.0.0':
				case '2.0.1':
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
						MODIFY COLUMN session_id CHAR(32) NOT NULL DEFAULT ''";
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . " TYPE=HEAP";
					
				case '2.0.2':
					$sql_update[] = "UPDATE " . LOG_TABLE . " SET send = 2 WHERE send = 1";
					$sql_update[] = $sql_create['ban_list'];
					
				case '2.1Beta':
				case '2.1Beta2':
				case '2.1.0':
				case '2.1.1':
				case '2.1.2':
				case '2.1.3':
				case '2.1.4':
					$sql_update[] = $sql_create['abo_liste'];
					$sql_update[] = $sql_create['joined_files'];
					$sql_update[] = $sql_create['log_files'];
					$sql_update[] = $sql_create['forbidden_ext'];
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
						ADD COLUMN session_ip CHAR(8) NOT NULL DEFAULT '',
						ADD COLUMN session_liste SMALLINT NOT NULL DEFAULT 0";
					break;
			}
			
			exec_queries($sql_update, true);
			
			//
			// Cr�ation/Modification de la table auth_admin
			//
			$sql_update = array();
			
			if( WA_BRANCHE == '2.0' )
			{
				$sql_update[] = $sql_create['auth_admin'];
				$field_level = 'droits';
			}
			else
			{
				$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . "
					ADD COLUMN auth_ban    TINYINT(1) NOT NULL DEFAULT 0,
					ADD COLUMN auth_attach TINYINT(1) NOT NULL DEFAULT 0,
					ADD COLUMN cc_admin    TINYINT(1) NOT NULL DEFAULT 0,
					ADD INDEX admin_id_idx (admin_id)";
				$field_level = 'level';
			}
			
			exec_queries($sql_update, true);
			
			//
			// Modifications sur la table admin
			//
			$sql_update = array();
			
			$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
				CHANGE user admin_login VARCHAR(30) NOT NULL DEFAULT '',
				CHANGE passwd admin_pwd VARCHAR(32) NOT NULL DEFAULT '',
				CHANGE email admin_email VARCHAR(255) NOT NULL DEFAULT '',
				CHANGE $field_level admin_level TINYINT(1) NOT NULL DEFAULT 1,
				ADD COLUMN admin_lang VARCHAR(30) NOT NULL DEFAULT '' AFTER admin_email,
				ADD COLUMN admin_dateformat VARCHAR(20) NOT NULL DEFAULT '' AFTER admin_lang,
				ADD COLUMN email_new_subscribe TINYINT(1) NOT NULL DEFAULT 0,
				ADD COLUMN email_unsubscribe TINYINT(1) NOT NULL DEFAULT 0";
			$sql_update[] = "UPDATE " . ADMIN_TABLE . "
				SET admin_lang = '" . $old_config['language'] . "',
				admin_dateformat = '" . $db->escape($old_config['date_format']) . "',
				email_new_subscribe = 0";
			
			if( WA_BRANCHE == '2.0' )
			{
				$sql_update[] = "UPDATE " . ADMIN_TABLE . "
					SET admin_level = 1 WHERE admin_level = 2";
				$sql_update[] = "UPDATE " . ADMIN_TABLE . "
					SET admin_level = 2 WHERE admin_level = 3";
			}
			
			exec_queries($sql_update, true);
			
			//
			// Modifications sur la table liste
			//
			$sql_update = array();
			$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
				CHANGE nom liste_name VARCHAR(100) NOT NULL DEFAULT '',
				CHANGE choix_format liste_format TINYINT(1) NOT NULL DEFAULT 1,
				CHANGE email_confirm confirm_subscribe TINYINT(1) NOT NULL DEFAULT 0,
				ADD COLUMN liste_public TINYINT(1) NOT NULL DEFAULT 1 AFTER liste_name,
				ADD COLUMN sender_email VARCHAR(250) NOT NULL DEFAULT '' AFTER liste_format,
				ADD COLUMN return_email VARCHAR(250) NOT NULL DEFAULT '' AFTER sender_email,
				ADD COLUMN liste_sig TEXT NOT NULL DEFAULT '',
				ADD COLUMN auto_purge TINYINT(1) NOT NULL DEFAULT 0,
				ADD COLUMN purge_freq TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
				ADD COLUMN purge_next INTEGER NOT NULL DEFAULT 0,
				ADD COLUMN liste_alias VARCHAR(250) NOT NULL DEFAULT '',
				ADD COLUMN liste_numlogs SMALLINT NOT NULL DEFAULT 0,
				ADD COLUMN liste_startdate INTEGER NOT NULL DEFAULT 0,
				ADD COLUMN use_cron TINYINT(1) NOT NULL DEFAULT 0,
				ADD COLUMN pop_host VARCHAR(100) NOT NULL DEFAULT '',
				ADD COLUMN pop_port SMALLINT NOT NULL DEFAULT 110,
				ADD COLUMN pop_user VARCHAR(100) NOT NULL DEFAULT '',
				ADD COLUMN pop_pass VARCHAR(100) NOT NULL DEFAULT ''";
			
			exec_queries($sql_update, true);
			
			$sql = "SELECT COUNT(*) AS numlogs, liste_id
				FROM " . LOG_TABLE . "
				WHERE send = " . STATUS_SENDED . "
				GROUP BY liste_id";
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			$num_logs_ary = array();
			while( $row = $result->fetch() )
			{
				$num_logs_ary[$row['liste_id']] = $row['numlogs'];
			}
			
			$sql = "SELECT liste_id, liste_name FROM " . LISTE_TABLE;
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			$sql_update = array();
			
			while( $row = $result->fetch() )
			{
				$numlogs = ( !empty($num_logs_ary[$row['liste_id']]) ) ? $num_logs_ary[$row['liste_id']] : 0;
				
				$sql_update[] = "UPDATE " . LISTE_TABLE . "
					SET liste_name      = '" . $db->escape(htmlspecialchars($row['liste_name'])) . "',
						sender_email    = '" . $db->escape($old_config['sender_email']) . "',
						return_email    = '" . $db->escape($old_config['return_email']) . "',
						liste_sig       = '" . $db->escape($old_config['signature']) . "',
						auto_purge      = '$old_config[auto_purge]',
						purge_freq      = '$old_config[purge_freq]',
						purge_next      = '$old_config[purge_next]',
						liste_startdate = $startdate,
						liste_numlogs   = $numlogs
					WHERE liste_id = " . $row['liste_id'];
			}
			
			if( WA_BRANCHE == '2.1' )
			{
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " DROP COLUMN email_new_inscrit";
			}
			
			exec_queries($sql_update, true);
			
			//
			// Modifications sur la table log
			//
			$sql = "SELECT log_id, attach FROM " . LOG_TABLE;
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			$logrow = $result->fetchAll();
			$result->free();
			
			$sql_update = array();
			$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
				CHANGE sujet log_subject VARCHAR(100) NOT NULL DEFAULT '',
				CHANGE body_html log_body_html TEXT NOT NULL DEFAULT '',
				CHANGE body_text log_body_text TEXT NOT NULL DEFAULT '',
				CHANGE date log_date INTEGER NOT NULL DEFAULT 0,
				CHANGE send log_status TINYINT(1) NOT NULL DEFAULT 0,
				ADD INDEX liste_id_idx (liste_id),
				ADD INDEX log_status_idx (log_status)";
			$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
				ADD COLUMN log_numdest SMALLINT NOT NULL DEFAULT 0";
			$sql_update[] = "ALTER TABLE " . LOG_TABLE . " DROP COLUMN attach";
			
			exec_queries($sql_update, true);
			
			require WAMAILER_DIR . '/class.mailer.php';
			
			$total_log = count($logrow);
			for( $i = 0; $i < $total_log; $i++ )
			{
				if( $logrow[$i]['attach'] == '' )
				{
					continue;
				}
				
				$files = array_map('trim', explode(',', $logrow[$i]['attach']));
				
				for( $j = 0, $total_files = count($files); $j < $total_files; $j++ )
				{
					$mime_type = Mailer::mime_type(substr($files[$j], (strrpos($files[$j], '.') + 1)));
					
					$filesize = 0;
					if( file_exists(WA_ROOTDIR . '/admin/upload/' . $files[$j]) )
					{
						$filesize = filesize(WA_ROOTDIR . '/admin/upload/' . $files[$j]);
					}
					
					$sql = "INSERT INTO " . JOINED_FILES_TABLE . " (file_real_name, file_physical_name, file_size, file_mimetype)
						VALUES('" . $db->escape($files[$j]) . "', '" . $db->escape($files[$j]) . "', " . intval($filesize) . ", '" . $db->escape($mime_type) . "')";
					if( $db->query($sql) )
					{
						$file_id = $db->lastInsertId();
						
						$sql = "INSERT INTO " . LOG_FILES_TABLE . " (log_id, file_id)
							VALUES(" . $logrow[$i]['log_id'] . ", $file_id)";
						if( !$db->query($sql) )
						{
							sql_error();
						}
					}
				}
			}
			
			unset($logrow);
			
			//
			// Modifications sur la table abonnes et insertions table abo_liste +
			// �limination des doublons
			//
			$sql = "SELECT * FROM " . ABONNES_TABLE;
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			$aborow = $result->fetchAll();
			$result->free();
			
			$sql_update	  = array();
			$sql_update[] = "DROP TABLE " . ABONNES_TABLE;
			$sql_update[] = $sql_create['abonnes'];
			
			exec_queries($sql_update, true);
			
			$abo_liste = array();
			for( $i = 0, $m = count($aborow); $i < $m; $i++ )
			{
				if( !isset($abo_liste[$aborow[$i]['email']]) )
				{
					$abo_liste[$aborow[$i]['email']] = array();
					
					$abo_liste[$aborow[$i]['email']]['code']   = $aborow[$i]['code'];
					$abo_liste[$aborow[$i]['email']]['date']   = $aborow[$i]['date'];
					$abo_liste[$aborow[$i]['email']]['status'] = $aborow[$i]['actif'];
					$abo_liste[$aborow[$i]['email']]['listes'] = array();
				}
				
				$abo_liste[$aborow[$i]['email']]['listes'][$aborow[$i]['liste_id']] = array(
					'format' => $aborow[$i]['format'],
					'send'   => ( !empty($aborow[$i]['send']) ) ? $aborow[$i]['send'] : 0
				);
			}
			
			foreach( $abo_liste as $email => $data )
			{
				$sql = "INSERT INTO " . ABONNES_TABLE . " (abo_email, abo_pwd, abo_lang, abo_status)
					VALUES('" . $db->escape($email) . "', '" . md5($data['code']) . "', '$language', " . $data['status'] . ")";
				exec_queries($sql, true);
				
				$abo_id = $db->lastInsertId();
				$sql_update = array();
				
				$data['code'] = ( $data['status'] == ABO_INACTIF ) ? '\'' . substr($data['code'], 0, 20) . '\'' : 'NULL';
				
				foreach( $data['listes'] as $liste_id => $listdata )
				{
					$sql_update[] = "INSERT INTO " . ABO_LISTE_TABLE . " (abo_id, liste_id, format, send, register_key, register_date, confirmed)
						VALUES($abo_id, $liste_id, $listdata[format], $listdata[send], $data[code], $data[date], $data[status])";
				}
				
				exec_queries($sql_update, true);
			}
			
			$sql = "SELECT abo_id, liste_id
				FROM " . ABO_LISTE_TABLE . "
				WHERE register_key IS NULL";
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			while( $row = $result->fetch() )
			{
				$sql = "UPDATE " . ABO_LISTE_TABLE . "
					SET register_key = '" . generate_key(20, false) . "'
					WHERE liste_id = $row[liste_id]
						AND abo_id = " . $row['abo_id'];
				$db->query($sql);
			}
			$result->free();
			
			unset($aborow, $abo_liste);
			
			//
			// Mise � jour de la table des logs
			//
			$sql_update = array();
			
			$sql = "SELECT COUNT(DISTINCT(a.abo_id)) AS num_dest, al.liste_id
				FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al
				WHERE a.abo_id = al.abo_id AND a.abo_status = " . ABO_ACTIF . "
				GROUP BY al.liste_id";
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			while( $row = $result->fetch() )
			{
				$sql_update[] = "UPDATE " . LOG_TABLE . "
					SET log_numdest = $row[num_dest]
					WHERE liste_id = " . $row['liste_id'];
			}
			
			exec_queries($sql_update, true);
		}
		else if( WA_BRANCHE == '2.2' )
		{
			$sql_update = array();
			
			switch( WA_VERSION )
			{
				case '2.2-Beta':
				case '2.2-Beta2':
					switch( SQL_DRIVER )
					{
						case 'postgres':
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								RENAME COLUMN smtp_user TO smtp_user_old,
								RENAME COLUMN smtp_pass TO smtp_pass_old";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN smtp_user varchar(100) NOT NULL DEFAULT '',
								ADD COLUMN smtp_pass varchar(100) NOT NULL DEFAULT ''";
							$sql_update[] = "UPDATE " . CONFIG_TABLE . "
								SET smtp_user = smtp_user_old, smtp_pass = smtp_pass_old";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								DROP COLUMN smtp_user_old,
								DROP COLUMN smtp_pass_old";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_alias VARCHAR(250) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN use_cron SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN pop_host VARCHAR(100) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN pop_port SMALLINT NOT NULL DEFAULT 110";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN pop_user VARCHAR(100) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN pop_pass VARCHAR(100) NOT NULL DEFAULT ''";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								MODIFY COLUMN smtp_user VARCHAR(100) NOT NULL DEFAULT '',
								MODIFY COLUMN smtp_pass VARCHAR(100) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_alias VARCHAR(250) NOT NULL DEFAULT '',
								ADD COLUMN use_cron TINYINT(1) NOT NULL DEFAULT 0,
								ADD COLUMN pop_host VARCHAR(100) NOT NULL DEFAULT '',
								ADD COLUMN pop_port SMALLINT NOT NULL DEFAULT 110,
								ADD COLUMN pop_user VARCHAR(100) NOT NULL DEFAULT '',
								ADD COLUMN pop_pass VARCHAR(100) NOT NULL DEFAULT ''";
							break;
					}
				
				//
				// Un bug �tait pr�sent dans la rc1, comme une seconde �dition du package avait �t� mise
				// � disposition pour pallier � un bug de derni�re minute assez important, le num�ro de version
				// �tait 2.2-RC2 pendant une dizaine de jours (alors qu'il me semblait avoir recorrig�
				// le package apr�s coup).
				// Nous effectuons donc la mise � jour �galement pour les versions 2.2-RC2.
				// Le nom de la vrai release candidate 2 est donc 2.2-RC2b pour �viter des probl�mes lors des mises
				// � jour par les gens qui ont t�l�charg� le package les dix premiers jours.
				//
				case '2.2-RC1':
				case '2.2-RC2':
					//
					// Suppression des �ventuelles entr�es orphelines dans les tables abonnes et abo_liste
					//
					$sql = "SELECT abo_id
						FROM " . ABONNES_TABLE;
					if( !($result = $db->query($sql)) )
					{
						sql_error();
					}
					
					$abonnes_id = array();
					while( $abo_id = $result->column('abo_id') )
					{
						array_push($abonnes_id, $abo_id);
					}
					
					$sql = "SELECT abo_id
						FROM " . ABO_LISTE_TABLE . "
						GROUP BY abo_id";
					if( !($result = $db->query($sql)) )
					{
						sql_error();
					}
					
					$abo_liste_id = array();
					while( $abo_id = $result->column('abo_id') )
					{
						array_push($abo_liste_id, $abo_id);
					}
					
					$diff_1 = array_diff($abonnes_id, $abo_liste_id);
					$diff_2 = array_diff($abo_liste_id, $abonnes_id);
					
					$total_diff_1 = count($diff_1);
					$total_diff_2 = count($diff_2);
					
					if( $total_diff_1 > 0 )
					{
						$sql_update[] = "DELETE FROM " . ABONNES_TABLE . "
							WHERE abo_id IN(" . implode(', ', $diff_1) . ")";
					}
					
					if( $total_diff_2 > 0 )
					{
						$sql_update[] = "DELETE FROM " . ABO_LISTE_TABLE . "
							WHERE abo_id IN(" . implode(', ', $diff_2) . ")";
					}
					
					switch( SQL_DRIVER )
					{
						case 'postgres':
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_numlogs SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
								ADD COLUMN log_numdest SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN check_email_mx SMALLINT NOT NULL DEFAULT 0";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_numlogs SMALLINT NOT NULL DEFAULT 0 AFTER liste_alias";
							$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
								ADD COLUMN log_numdest SMALLINT NOT NULL DEFAULT 0 AFTER log_date";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN check_email_mx TINYINT(1) NOT NULL DEFAULT 0 AFTER gd_img_type";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " DROP INDEX abo_id";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " DROP INDEX liste_id";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
								ADD PRIMARY KEY (abo_id , liste_id)";
							$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " DROP INDEX log_id";
							$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " DROP INDEX file_id";
							$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . "
								ADD PRIMARY KEY (log_id , file_id)";
							break;
					}
					
					$sql = "SELECT COUNT(*) AS numlogs, liste_id
						FROM " . LOG_TABLE . "
						WHERE log_status = " . STATUS_SENDED . "
						GROUP BY liste_id";
					if( !($result = $db->query($sql)) )
					{
						sql_error();
					}
					
					while( $row = $result->fetch() )
					{
						$sql_update[] = "UPDATE " . LISTE_TABLE . "
							SET liste_numlogs = " . $row['numlogs'] . "
							WHERE liste_id = " . $row['liste_id'];
					}
					
					$sql = "SELECT COUNT(DISTINCT(a.abo_id)) AS num_dest, al.liste_id
						FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al
						WHERE a.abo_id = al.abo_id AND a.abo_status = " . ABO_ACTIF . "
						GROUP BY al.liste_id";
					if( !($result = $db->query($sql)) )
					{
						sql_error();
					}
					
					while( $row = $result->fetch() )
					{
						$sql_update[] = "UPDATE " . LOG_TABLE . "
							SET log_numdest = " . $row['num_dest'] . "
							WHERE liste_id = " . $row['liste_id'];
					}
				
				case '2.2-RC2b':
					switch( SQL_DRIVER )
					{
						case 'postgres':
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN enable_profil_cp SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
								ADD COLUMN abo_lang VARCHAR(30) NOT NULL DEFAULT ''";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN enable_profil_cp TINYINT(1) NOT NULL DEFAULT 0 AFTER check_email_mx";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
								ADD COLUMN abo_lang VARCHAR(30) NOT NULL DEFAULT '' AFTER abo_email";
							break;
					}
					
					//
					// Correction du bug de mise � jour de la table abo_liste apr�s un envoi.
					// Si tous les abonn�s d'une liste ont send � 1, on remet celui ci � 0
					//
					$sql = "SELECT COUNT(al.abo_id) AS num_abo, SUM(al.send) AS num_send, al.liste_id
						FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al
						WHERE a.abo_id = al.abo_id AND a.abo_status = " . ABO_ACTIF . "
						GROUP BY al.liste_id";
					if( !($result = $db->query($sql)) )
					{
						sql_error();
					}
					
					while( $row = $result->fetch() )
					{
						if( $row['num_abo'] == $row['num_send'] )
						{
							$sql_update[] = "UPDATE " . ABO_LISTE_TABLE . "
								SET send = 0
								WHERE liste_id = " . $row['liste_id'];
						}
					}
					
					$sql_update[] = "UPDATE " . ABONNES_TABLE . " SET abo_lang = '$language'";
					
				case '2.2-RC3':
					switch( SQL_DRIVER )
					{
						case 'postgres':
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN ftp_port SMALLINT NOT NULL DEFAULT 21";
							break;

						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN ftp_port SMALLINT NOT NULL DEFAULT 21 AFTER ftp_server";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
								CHANGE abo_id abo_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
								CHANGE abo_id abo_id INTEGER UNSIGNED NOT NULL DEFAULT 0";
							break;
					}
					
				case '2.2-RC4':
				case '2.2.0':
				case '2.2.1':
				case '2.2.2':
				case '2.2.3':
				case '2.2.4':
				case '2.2.5':
				case '2.2.6':
				case '2.2.7':
				case '2.2.8':
				case '2.2.9':
				case '2.2.10':
				case '2.2.11':
				case '2.2.12':
					$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
						DROP COLUMN hebergeur, DROP COLUMN version";
					
					if( SQL_DRIVER == 'postgres' )
					{
						$sql_update[] = "DROP INDEX abo_status_wa_abonnes_index";
						$sql_update[] = "DROP INDEX admin_id_wa_auth_admin_index";
						$sql_update[] = "DROP INDEX liste_id_wa_log_index";
						$sql_update[] = "DROP INDEX log_status_wa_log_index";
						$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
							RENAME COLUMN email_new_inscrit email_new_subscribe";
						$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
							ADD COLUMN email_unsubscribe SMALLINT NOT NULL DEFAULT 0";
						$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . "
							ADD COLUMN cc_admin SMALLINT NOT NULL DEFAULT 0";
						$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
							ADD COLUMN liste_public SMALLINT NOT NULL DEFAULT 1";
					}
					else
					{
						$sql_update[] = "DROP INDEX abo_status ON " . ABONNES_TABLE;
						$sql_update[] = "DROP INDEX admin_id ON " . AUTH_ADMIN_TABLE;
						$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
							DROP INDEX liste_id,
							DROP INDEX log_status";
						$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
							CHANGE email_new_inscrit email_new_subscribe TINYINT(1) NOT NULL DEFAULT 0,
							ADD COLUMN email_unsubscribe TINYINT(1) NOT NULL DEFAULT 0";
						$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . "
							ADD COLUMN cc_admin TINYINT(1) NOT NULL DEFAULT 0";
						$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
							ADD COLUMN liste_public TINYINT(1) NOT NULL DEFAULT 1 AFTER liste_name";
					}
					
					$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
						ADD COLUMN register_key CHAR(20) DEFAULT NULL,
						ADD COLUMN register_date INTEGER NOT NULL DEFAULT 0,
						ADD COLUMN confirmed SMALLINT NOT NULL DEFAULT 0";
					
					exec_queries($sql_update, true);
					
					$sql = "SELECT abo_id, abo_register_key, abo_pwd, abo_register_date, abo_status
						FROM " . ABONNES_TABLE;
					if( !($result = $db->query($sql)) )
					{
						sql_error();
					}
					
					while( $row = $result->fetch() )
					{
						$sql = "UPDATE " . ABO_LISTE_TABLE . "
							SET register_date = $row[abo_register_date],
								confirmed     = $row[abo_status]";
						if( $row['abo_status'] == ABO_INACTIF )
						{
							$sql .= ", register_key = '" . substr($row['abo_register_key'], 0, 20) . "'";
						}
						$db->query($sql . " WHERE abo_id = " . $row['abo_id']);
						
						if( empty($row['abo_pwd']) )
						{
							$db->query("UPDATE " . ABONNES_TABLE . "
								SET abo_pwd = '" . md5($row['abo_register_key']) . "'
								WHERE abo_id = $row[abo_id]");
						}
					}
					$result->free();
					
					$sql = "SELECT abo_id, liste_id
						FROM " . ABO_LISTE_TABLE . "
						WHERE register_key IS NULL";
					if( !($result = $db->query($sql)) )
					{
						sql_error();
					}
					
					while( $row = $result->fetch() )
					{
						$sql = "UPDATE " . ABO_LISTE_TABLE . "
							SET register_key = '" . generate_key(20, false) . "'
							WHERE liste_id = $row[liste_id]
								AND abo_id = " . $row['abo_id'];
						$db->query($sql);
					}
					$result->free();
					
					$sql_update = array();
					$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
						DROP COLUMN abo_register_key,
						DROP COLUMN abo_register_date";
					
					if( SQL_DRIVER == 'postgres' )
					{
						$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
							ADD CONSTRAINT register_key_idx UNIQUE (register_key)";
						$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
							ADD CONSTRAINT abo_email_idx UNIQUE (abo_email)";
						$sql_update[] = "CREATE INDEX abo_status_idx ON " . ABONNES_TABLE . " (abo_status)";
						$sql_update[] = "CREATE INDEX admin_id_idx ON " . AUTH_ADMIN_TABLE . " (admin_id)";
						$sql_update[] = "CREATE INDEX liste_id_idx ON " . LOG_TABLE . " (liste_id)";
						$sql_update[] = "CREATE INDEX log_status_idx ON " . LOG_TABLE . " (log_status)";
					}
					else
					{
						$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
							ADD UNIQUE register_key_idx (register_key)";
						$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
							ADD UNIQUE abo_email_idx (abo_email),
							ADD INDEX abo_status_idx (abo_status)";
						$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . "
							ADD INDEX admin_id_idx (admin_id)";
						$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
							ADD INDEX liste_id_idx (liste_id),
							ADD INDEX log_status_idx (log_status)";
					}
					break;
				
				default:
					message($lang['Upgrade_not_required']);
					break;
			}
			
			exec_queries($sql_update, true);
		}
		else if( WA_BRANCHE == '2.3' || WA_BRANCHE == '2.4' )
		{
			$version = str_replace('rc', 'RC', WA_VERSION);
			$sql_update = array();
			
			if( !version_compare($version, WA_NEW_VERSION, '<' ) )
			{
				message($lang['Upgrade_not_required']);
			}
			
			if( version_compare($version, '2.3-beta3', '<=') )
			{
				//
				// En cas de bug lors d'une importation d'emails, les clefs
				// peuvent ne pas avoir �t� recr��es si une erreur est survenue
				//
				if( SQL_DRIVER == 'postgres' )
				{
					$db->query("ALTER TABLE " . ABONNES_TABLE . "
						ADD CONSTRAINT abo_email_idx UNIQUE (abo_email)");
				}
				else if( strncmp(SQL_DRIVER, 'mysql', 5) == 0 )
				{
					$db->query("ALTER TABLE " . ABONNES_TABLE . "
						ADD UNIQUE abo_email_idx (abo_email)");
				}
			}
			
			exec_queries($sql_update, true);
		}
		
		//
		// Modification fichier de configuration +
		// Affichage message de r�sultat
		//
		if( !is_writable(WA_ROOTDIR . '/includes/config.inc.php') )
		{
			$output->addHiddenField('driver',  $infos['driver']);
			$output->addHiddenField('host',    $infos['host']);
			$output->addHiddenField('user',    $infos['user']);
			$output->addHiddenField('pass',    $infos['pass']);
			$output->addHiddenField('dbname',  $infos['dbname']);
			$output->addHiddenField('prefixe', $prefixe);
			
			$output->assign_block_vars('download_file', array(
				'L_TITLE'         => $lang['Result_upgrade'],
				'L_DL_BUTTON'     => $lang['Button']['dl'],
				
				'MSG_RESULT'      => nl2br($lang['Success_without_config']),						
				'S_HIDDEN_FIELDS' => $output->getHiddenFields()
			));
			
			$output->pparse('body');
			exit;
		}
		
		$fw = fopen(WA_ROOTDIR . '/includes/config.inc.php', 'w');
		fwrite($fw, $config_file);
		fclose($fw);
		
		//
		// Modification fichier de configuration +
		// Affichage message de r�sultat
		//
		$message = sprintf($lang['Success_upgrade'], '<a href="' . WA_ROOTDIR . '/admin/login.php">', '</a>');
		
		message($message, $lang['Result_upgrade']);
	}
}

$output->assign_block_vars('upgrade', array(
	'L_EXPLAIN'      => nl2br(sprintf($lang['Welcome_in_upgrade'], WA_VERSION)),
	'L_LOGIN'        => $lang['Login'],
	'L_PASS'         => $lang['Password'],
	'L_START_BUTTON' => $lang['Start_upgrade']
));

if( $error )
{
	$output->error_box($msg_error);
}

$output->pparse('body');

?>
