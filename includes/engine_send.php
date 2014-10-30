<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aur�lien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if (!defined('ENGINE_SEND_INC')) {

define('ENGINE_SEND_INC', true);

include WA_ROOTDIR . '/includes/tags.inc.php';

/**
 * launch_sending()
 *
 * Cette fonction est appell�e soit dans envoi.php lors de l'envoi, soit
 * dans le fichier appell� originellement cron.php
 *
 * @param array $listdata      Tableau des donn�es de la liste concern�e
 * @param array $logdata       Tableau des donn�es de la newsletter
 * @param array $supp_address  Adresses de destinataires suppl�mentaires
 *
 * @return string
 */
function launch_sending($listdata, $logdata, $supp_address = array())
{
	global $nl_config, $db, $lang, $other_tags;

	//
	// On commence par poser un verrou sur un fichier lock,
	// il ne faut pas qu'il y ait simultan�ment plusieurs fl�ts d'envois
	// pour une m�me liste de diffusion.
	//
	$lockfile = sprintf(WA_LOCKFILE, $listdata['liste_id']);

	if (file_exists($lockfile)) {
		$isBeginning = false;
		$fp = fopen($lockfile, 'r+');
		$supp_address = array();// On en tient pas compte, �a l'a d�j� �t� lors du premier fl�t
	}
	else {
		$isBeginning = true;
		$fp = fopen($lockfile, 'w');
		@chmod($lockfile, 0600);
	}

	if (!flock($fp, LOCK_EX|LOCK_NB)) {
		fclose($fp);
		return $lang['Message']['List_is_busy'];
	}

	if (filesize($lockfile) > 0) {
		//
		// L'envoi a plant� au cours d'un "fl�t" pr�c�dent. On r�cup�re les �ventuels
		// identifiants d'abonn�s stock�s dans le fichier lock et on met � jour la table
		//
		$abo_ids = fread($fp, filesize($lockfile));
		$abo_ids = array_map('trim', explode("\n", trim($abo_ids)));

		if (count($abo_ids) > 0) {
			$abo_ids = array_unique(array_map('intval', $abo_ids));

			$sql = "UPDATE " . ABO_LISTE_TABLE . "
				SET send = 1
				WHERE abo_id IN(" . implode(', ', $abo_ids) . ")
					AND liste_id = " . $listdata['liste_id'];
			$db->query($sql);
		}

		ftruncate($fp, 0);
		fseek($fp, 0);
	}

	//
	// Initialisation de la classe mailer
	//
	$mailer = new Mailer(WA_ROOTDIR . '/language/email_' . $nl_config['language'] . '/');
	$mailer->signature = WA_X_MAILER;

	if ($nl_config['use_smtp']) {
		$mailer->use_smtp(
			$nl_config['smtp_host'],
			$nl_config['smtp_port'],
			$nl_config['smtp_user'],
			$nl_config['smtp_pass']
		);
	}

	$mailer->set_charset($lang['CHARSET']);
	$mailer->set_from($listdata['sender_email'], $listdata['liste_name']);

	if ($listdata['return_email'] != '') {
		$mailer->set_return_path($listdata['return_email']);
	}

	//
	// On traite les donn�es de la newsletter � envoyer
	//
	if (preg_match('/[\x80-\x9F]/', $logdata['log_subject']) ||
		preg_match('/[\x80-\x9F]/', $logdata['log_body_text']) ||
		preg_match('/[\x80-\x9F]/', $logdata['log_body_html'])
	) {
		if (!TRANSLITE_INVALID_CHARS) {
			$logdata['log_subject']   = wan_utf8_encode($logdata['log_subject']);
			$logdata['log_body_text'] = wan_utf8_encode($logdata['log_body_text']);
			$logdata['log_body_html'] = wan_utf8_encode($logdata['log_body_html']);
			$lang['Label_link']       = wan_utf8_encode($lang['Label_link']);

			$mailer->set_charset('UTF-8');
		}
		else {
			$logdata['log_subject']   = purge_latin1($logdata['log_subject'], true);
			$logdata['log_body_text'] = purge_latin1($logdata['log_body_text'], true);
			$logdata['log_body_html'] = purge_latin1($logdata['log_body_html']);
		}
	}

	$mailer->set_subject($logdata['log_subject']);

	$body = array(
		FORMAT_TEXTE => $logdata['log_body_text'],
		FORMAT_HTML  => $logdata['log_body_html']
	);

	//
	// Ajout du lien de d�sinscription, selon les m�thodes d'envoi/format utilis�s
	//
	$link = newsletter_links($listdata);

	if ($listdata['use_cron'] || $nl_config['engine_send'] == ENGINE_BCC) {
		$body[FORMAT_TEXTE] = str_replace('{LINKS}', $link[FORMAT_TEXTE], $body[FORMAT_TEXTE]);
		$body[FORMAT_HTML]  = str_replace('{LINKS}', $link[FORMAT_HTML],  $body[FORMAT_HTML]);
	}

	//
	// On s'occupe maintenant des fichiers joints ou incorpor�s
	// Si les fichiers sont stock�s sur un serveur ftp, on les rapatrie le temps du flot d'envoi
	//
	$total_files = count($logdata['joined_files']);
	$tmp_files   = array();

	$attach = new Attach();

	hasCidReferences($body[FORMAT_HTML], $refs);

	for ($i = 0; $i < $total_files; $i++) {
		$real_name     = $logdata['joined_files'][$i]['file_real_name'];
		$physical_name = $logdata['joined_files'][$i]['file_physical_name'];
		$mime_type     = $logdata['joined_files'][$i]['file_mimetype'];

		$error = false;
		$msg   = array();

		$attach->joined_file_exists($physical_name, $error, $msg);

		if ($error) {
			$error = false;
			continue;
		}

		if ($nl_config['use_ftp']) {
			$file_path = $attach->ftp_to_tmp($logdata['joined_files'][$i]);
			$tmp_files[] = $file_path;
		}
		else {
			$file_path = WA_ROOTDIR . '/' . $nl_config['upload_path'] . $physical_name;
		}

		if (is_array($refs) && in_array($real_name, $refs)) {
			$embedded = true;
		}
		else {
			$embedded = false;
		}

		$mailer->attachment($file_path, $real_name, 'attachment', $mime_type, $embedded);
	}

	//
	// R�cup�ration des champs des tags personnalis�s
	//
	if (count($other_tags) > 0) {
		$fields_str = '';
		foreach ($other_tags as $tag) {
			$fields_str .= 'a.' . $tag['column_name'] . ', ';
		}
	}
	else {
		$fields_str = '';
	}

	//
	// Si on en est au premier fl�t, on r�cup�re �galement les adresses email
	// des administrateurs ayant activ�s l'option de r�ception de copie
	//
	if ($isBeginning) {
		$sql = "SELECT a.admin_email
			FROM " . ADMIN_TABLE . " AS a
				INNER JOIN " . AUTH_ADMIN_TABLE . " AS aa ON aa.admin_id = a.admin_id
					AND aa.cc_admin = " . true;
		$result = $db->query($sql);

		while ($email = $result->column('admin_email')) {
			$supp_address[] = $email;
		}
		$result->free();

		$supp_address = array_unique($supp_address); // Au cas o�...
	}

	$abo_ids     = array();
	$total_abo   = 0;
	$abo_address = array();
	$format      = ($listdata['liste_format'] != FORMAT_MULTIPLE) ? $listdata['liste_format'] : false;

	if ($logdata['log_status'] == STATUS_STANDBY) {
		//
		// On r�cup�re les infos sur les abonn�s destinataires
		//
		$sql = "SELECT COUNT(a.abo_id) AS total
			FROM " . ABONNES_TABLE . " AS a
				INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
					AND al.liste_id  = $listdata[liste_id]
					AND al.confirmed = " . SUBSCRIBE_CONFIRMED . "
					AND al.send      = 0
			WHERE a.abo_status = " . ABO_ACTIF;
		$result = $db->query($sql);

		$total_abo = $result->column('total');
		if ($nl_config['sending_limit'] > 0) {
			$total_abo = min($total_abo, $nl_config['sending_limit']);
		}

		$sql = "SELECT a.abo_id, a.abo_pseudo, $fields_str a.abo_email, al.register_key, al.format
			FROM " . ABONNES_TABLE . " AS a
				INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
					AND al.liste_id  = $listdata[liste_id]
					AND al.confirmed = " . SUBSCRIBE_CONFIRMED . "
					AND al.send      = 0
			WHERE a.abo_status = " . ABO_ACTIF;
		if ($nl_config['sending_limit'] > 0) {
			$sql .= " LIMIT $nl_config[sending_limit] OFFSET 0";
		}

		$result = $db->query($sql);

		while ($row = $result->fetch()) {
			$abo_address[] = $row;
		}
	}

	if (count($abo_address) > 0 || ($logdata['log_status'] != STATUS_STANDBY && count($supp_address) > 0)) {
		if ($nl_config['engine_send'] == ENGINE_BCC) {
			$abonnes = array(FORMAT_TEXTE => array(), FORMAT_HTML => array());
			$abo_ids = array(FORMAT_TEXTE => array(), FORMAT_HTML => array());

			foreach ($abo_address as $row) {
				$abo_format = (!$format) ? $row['format'] : $format;
				$abo_ids[$abo_format][] = $row['abo_id'];
				$abonnes[$abo_format][] = $row['abo_email'];
			}

			if ($listdata['liste_format'] != FORMAT_HTML) {
				$abonnes[FORMAT_TEXTE] = array_merge($abonnes[FORMAT_TEXTE], $supp_address);
			}

			if ($listdata['liste_format'] != FORMAT_TEXTE) {
				$abonnes[FORMAT_HTML] = array_merge($abonnes[FORMAT_HTML], $supp_address);
			}

			//
			// Tableau pour remplacer les tags par des chaines vides
			// Non utilisation des tags avec le moteur d'envoi en copie cach�e
			//
			$tags_replace = array('NAME' => '', 'PSEUDO' => '');
			if (count($other_tags) > 0) {
				foreach ($other_tags as $tag) {
					$tags_replace[$tag['tag_name']] = '';
				}
			}

			if (count($abonnes[FORMAT_TEXTE]) > 0) {
				$mailer->set_address($abonnes[FORMAT_TEXTE], 'Bcc');
				$mailer->set_format(FORMAT_TEXTE);
				$mailer->set_message($body[FORMAT_TEXTE]);
				$mailer->assign_tags($tags_replace);

				if (!$mailer->send()) {
					trigger_error(sprintf($lang['Message']['Failed_sending2'], $mailer->msg_error), E_USER_ERROR);
				}

				fwrite($fp, implode("\n", $abo_ids[FORMAT_TEXTE])."\n");
			}

			$mailer->clear_address();

			if (count($abonnes[FORMAT_HTML]) > 0) {
				$mailer->set_address($abonnes[FORMAT_HTML], 'Bcc');
				$mailer->set_format($listdata['liste_format']);
				$mailer->assign_tags($tags_replace);
				$mailer->set_message($body[FORMAT_HTML]);

				if ($listdata['liste_format'] == FORMAT_MULTIPLE) {
					$mailer->set_altmessage($body[FORMAT_TEXTE]);
				}

				if (!$mailer->send()) {
					trigger_error(sprintf($lang['Message']['Failed_sending2'], $mailer->msg_error), E_USER_ERROR);
				}

				fwrite($fp, implode("\n", $abo_ids[FORMAT_HTML])."\n");
			}

			$abo_ids = array_merge($abo_ids[FORMAT_TEXTE], $abo_ids[FORMAT_HTML]);
		}
		else if ($nl_config['engine_send'] == ENGINE_UNIQ) {
			if (defined('IN_COMMANDLINE')) {
				//
				// Initialisation de la barre de progression des envois
				//
				$bar = new Console_ProgressBar(
					'Sending emails %percent% [%bar%] %current% of %max%', // One of several predefined formatstrings
					'=>',       // What are we filling the bar with
					' ',        // What are we PRE-filing the bar with
					80,         // How wide is the  bar
					$total_abo + count($supp_address), // How many steps are we looping through
					array('ansi_terminal' => ANSI_TERMINAL)
				);
			}
			else {
				fake_header(false);
			}

			$mailerText = clone $mailer;
			$mailerHTML = clone $mailer;

			if (!$listdata['use_cron']) {
				$body[FORMAT_TEXTE] = str_replace('{LINKS}', $link[FORMAT_TEXTE], $body[FORMAT_TEXTE]);
				$body[FORMAT_HTML]  = str_replace('{LINKS}', $link[FORMAT_HTML], $body[FORMAT_HTML]);
			}

			$mailerText->set_format(FORMAT_TEXTE);
			$mailerText->set_message($body[FORMAT_TEXTE]);

			$mailerHTML->set_format(FORMAT_HTML);
			if ($listdata['liste_format'] == FORMAT_MULTIPLE) {
				$mailerHTML->set_format(FORMAT_MULTIPLE);
				$mailerHTML->set_altmessage($body[FORMAT_TEXTE]);
			}
			$mailerHTML->set_message($body[FORMAT_HTML]);

			$supp_address_ok = array();
			foreach ($supp_address as $address) {
				if ($listdata['liste_format'] != FORMAT_HTML) {
					$supp_address_ok[] = array(
						'format' => FORMAT_TEXTE,
						'abo_pseudo' => '',
						'abo_email'  => $address,
						'register_key' => '',
						'abo_id'     => -1
					);
				}

				if ($listdata['liste_format'] != FORMAT_TEXTE) {
					$supp_address_ok[] = array(
						'format' => FORMAT_HTML,
						'abo_pseudo' => '',
						'abo_email'  => $address,
						'register_key' => '',
						'abo_id'     => -1
					);
				}
			}

			$counter   = 0;
			$sendError = 0;

			while (($row = array_pop($abo_address)) != null || ($row = array_pop($supp_address_ok)) != null) {
				$counter++;
				$abo_format = (!$format) ? $row['format'] : $format;

				// Choix de l'instance de Wamailer en fonction du format voulu
				$mailer = ($abo_format == FORMAT_TEXTE) ? $mailerText : $mailerHTML;

				if ($row['abo_pseudo'] != '') {
					$address = array($row['abo_pseudo'] => $row['abo_email']);
				}
				else {
					$address = $row['abo_email'];
				}

				$mailer->clear_address();
				$mailer->set_address($address);

				//
				// Traitement des tags et tags personnalis�s
				//
				$tags_replace = array();

				if ($row['abo_pseudo'] != '') {
					$tags_replace['NAME'] = $row['abo_pseudo'];
					if ($abo_format == FORMAT_HTML) {
						$tags_replace['NAME'] = wan_htmlspecialchars($row['abo_pseudo']);
					}
				}
				else {
					$tags_replace['NAME'] = '';
				}
				$tags_replace['PSEUDO'] = $tags_replace['NAME'];// Coh�rence avec d'autres parties du script

				if (count($other_tags) > 0) {
					foreach ($other_tags as $tag) {
						if (isset($row[$tag['column_name']])) {
							if (!is_numeric($row[$tag['column_name']]) && $abo_format == FORMAT_HTML) {
								$row[$tag['column_name']] = wan_htmlspecialchars($row[$tag['column_name']]);
							}

							$tags_replace[$tag['tag_name']] = $row[$tag['column_name']];

							continue;
						}

						$tags_replace[$tag['tag_name']] = '';
					}
				}

				if (!$listdata['use_cron']) {
					$tags_replace = array_merge($tags_replace, array(
						'WA_CODE'  => $row['register_key'],
						'WA_EMAIL' => $row['abo_email']
					));
				}

				$mailer->assign_tags($tags_replace);

				// envoi
				if (!$mailer->send()) {
					$sendError++;
				}

				if ($row['abo_id'] != -1) {
					$abo_ids[] = $row['abo_id'];
					fwrite($fp, "$row[abo_id]\n");
				}

				if (defined('IN_COMMANDLINE')) {
					$bar->update($counter);

					if (SEND_DELAY > 0 && ($counter % SEND_PACKET) == 0) {
						sleep(SEND_DELAY);
					}
				}
				else {
					fake_header(true);
				}
			}

			//
			// Aucun email envoy�, il y a manifestement un probl�me, on affiche le message d'erreur
			//
			if ($total_abo > 0 && $sendError == $total_abo) {
				flock($fp, LOCK_UN);
				fclose($fp);
				unlink($lockfile);

				trigger_error(sprintf($lang['Message']['Failed_sending2'], $mailer->msg_error), E_USER_ERROR);
			}
		}
		else {
			trigger_error('Unknown_engine', E_USER_ERROR);
		}

		$result->free();
	}
	else if ($isBeginning) {
		//
		// Aucun abonn� dont le champ send soit positionn� � 0 et nous sommes au
		// d�but de l'envoi. Cette liste ne comporte donc pas encore d'abonn�.
		//
		return $lang['Message']['No_subscribers'];
	}

	//
	// Si l'option FTP est utilis�e, suppression des fichiers temporaires
	//
	if ($nl_config['use_ftp']) {
		foreach ($tmp_files as $filename) {
			$attach->remove_file($filename);
		}
	}
	unset($tmp_files);

	$no_sent = $sent = 0;

	if (!$db->ping()) {
		//
		// L'envoi a dur� trop longtemps et la connexion au serveur SQL a �t� perdue
		//
		if ($db::ENGINE == 'mysql') {
			trigger_error("La connexion � la base de donn�es a �t� perdue.<br />
Vous devriez mettre l'option PHP mysqli.reconnect � On dans le php.ini,<br />
pour permettre la reconnexion automatique au serveur.", E_USER_ERROR);
		}
		else {
			trigger_error("La connexion � la base de donn�es a �t� perdue", E_USER_ERROR);
		}
	}

	if (count($abo_ids) > 0) {
		$sql = "UPDATE " . ABO_LISTE_TABLE . "
			SET send = 1
			WHERE abo_id IN(" . implode(', ', $abo_ids) . ")
				AND liste_id = " . $listdata['liste_id'];
		$db->query($sql);
	}

	$sql = "SELECT COUNT(*) AS num_dest, al.send
		FROM " . ABO_LISTE_TABLE . " AS al
			INNER JOIN " . ABONNES_TABLE . " AS a ON a.abo_id = al.abo_id
				AND a.abo_status = " . ABO_ACTIF . "
		WHERE al.liste_id    = $listdata[liste_id]
			AND al.confirmed = " . SUBSCRIBE_CONFIRMED . "
		GROUP BY al.send";
	$result = $db->query($sql);

	while ($row = $result->fetch()) {
		if ($row['send'] == 1) {
			$sent  = $row['num_dest'];
		}
		else {
			$no_sent = $row['num_dest'];
		}
	}
	$result->free();

	ftruncate($fp, 0);
	flock($fp, LOCK_UN);
	fclose($fp);

	if ($logdata['log_status'] == STATUS_STANDBY && $no_sent > 0) {
		$message = sprintf(
			$lang['Message']['Success_send'],
			$nl_config['sending_limit'],
			$sent,
			($sent + $no_sent)
		);

		if (!defined('IN_COMMANDLINE')) {
			if (!empty($_GET['step']) && $_GET['step'] == 'auto') {
				http_redirect("envoi.php?mode=progress&id=$logdata[log_id]&step=auto");
			}

			$message .= '<br /><br />' .  sprintf($lang['Click_resend_auto'], sprintf('<a href="envoi.php?mode=progress&amp;id=%d&amp;step=auto">', $logdata['log_id']), '</a>');
			$message .= '<br /><br />' .  sprintf($lang['Click_resend_manuel'], sprintf('<a href="envoi.php?mode=progress&amp;id=%d">', $logdata['log_id']), '</a>');
		}
	}
	else {
		unlink($lockfile);

		if ($logdata['log_status'] == STATUS_STANDBY) {
			$db->beginTransaction();

			$sql = "UPDATE " . LOG_TABLE . "
				SET log_status = " . STATUS_SENT . ",
					log_numdest = $sent
				WHERE log_id = " . $logdata['log_id'];
			$db->query($sql);

			$sql = "UPDATE " . ABO_LISTE_TABLE . "
				SET send = 0
				WHERE liste_id = " . $listdata['liste_id'];
			$db->query($sql);

			$sql = "UPDATE " . LISTE_TABLE . "
				SET liste_numlogs = liste_numlogs + 1
				WHERE liste_id = " . $listdata['liste_id'];
			$db->query($sql);

			$db->commit();

			$message = sprintf($lang['Message']['Success_send_finish'], $sent);
		}
		else { // mode test
			$message  = $lang['Test_send_finish'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_back'], sprintf('<a href="envoi.php?mode=load&amp;id=%d">', $logdata['log_id']), '</a>');
		}
	}

	return $message;
}

/**
 * Fonction renvoyant les liens � placer dans les newsletters, selon les r�glages
 *
 * @param array $listdata  Tableau des donn�es de la liste concern�e
 *
 * @return array
 */
function newsletter_links($listdata)
{
	global $nl_config, $lang;

	$link = array(FORMAT_TEXTE => '', FORMAT_HTML => '');

	$link_template = sprintf('<a href="%%s">%s</a>', str_replace('%', '%%', $lang['Label_link']));

	if ($listdata['use_cron']) {
		$liste_email = (!empty($listdata['liste_alias'])) ? $listdata['liste_alias'] : $listdata['sender_email'];

		$link = array(
			FORMAT_TEXTE => $liste_email,
			FORMAT_HTML  => sprintf($link_template,
				sprintf('mailto:%s?subject=unsubscribe', $liste_email)
			)
		);
	}
	else {
		if ($nl_config['engine_send'] == ENGINE_BCC) {
			$link = array(
				FORMAT_TEXTE => $listdata['form_url'],
				FORMAT_HTML  => sprintf($link_template, wan_htmlspecialchars($listdata['form_url']))
			);
		}
		else {
			$tmp_link = $listdata['form_url'] . (strstr($listdata['form_url'], '?') ? '&' : '?') . '{WA_CODE}';

			$link = array(
				FORMAT_TEXTE => $tmp_link,
				FORMAT_HTML  => sprintf($link_template, wan_htmlspecialchars($tmp_link))
			);
		}
	}

	return $link;
}

}
