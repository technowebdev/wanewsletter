<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Patchwork\Utf8 as u;
use Wamailer\Mailer;
use Wamailer\Email;

require './start.inc.php';

$mode     = filter_input(INPUT_GET, 'mode');
$admin_id = (int) filter_input(INPUT_POST, 'uid', FILTER_VALIDATE_INT);

if (isset($_POST['cancel'])) {
	http_redirect('admin.php');
}

if (isset($_POST['delete_user'])) {
	$mode = 'deluser';
}

//
// Seuls les administrateurs peuvent ajouter ou supprimer un utilisateur
//
if (($mode == 'adduser' || $mode == 'deluser') && !wan_is_admin($admindata)) {
	http_response_code(401);
	$output->redirect('index.php', 4);
	$output->addLine($lang['Message']['Not_authorized']);
	$output->addLine($lang['Click_return_index'], './index.php');
	$output->displayMessage();
}

if ($mode == 'adduser') {
	$new_login = trim(u::filter_input(INPUT_POST, 'new_login'));
	$new_email = trim(u::filter_input(INPUT_POST, 'new_email'));

	if (isset($_POST['submit'])) {
		if (!validate_pseudo($new_login)) {
			$error = true;
			$msg_error[] = $lang['Message']['Invalid_login'];
		}
		else {
			$sql = "SELECT COUNT(*) AS login_test
				FROM " . ADMIN_TABLE . "
				WHERE admin_login = '" . $db->escape($new_login) . "'";
			$result = $db->query($sql);

			if ($result->column('login_test') > 0) {
				$error = true;
				$msg_error[] = $lang['Message']['Double_login'];
			}
		}

		if (!Mailer::checkMailSyntax($new_email)) {
			$error = true;
			$msg_error[] = $lang['Message']['Invalid_email'];
		}

		if (!$error) {
			$sql_data = array();
			$sql_data['admin_login']      = $new_login;
			$sql_data['admin_email']      = $new_email;
			$sql_data['admin_lang']       = $nl_config['language'];
			$sql_data['admin_dateformat'] = $nl_config['date_format'];
			$sql_data['admin_level']      = USER_LEVEL;

			$db->insert(ADMIN_TABLE, $sql_data);
			$admin_id = $db->lastInsertId();

			$tpl = new Template(WA_ROOTDIR . '/languages/' . $nl_config['language'] . '/emails/');
			$tpl->set_filenames(array('mail' => 'new_admin.txt'));
			$tpl->assign_vars(array(
				'PSEUDO'        => $new_login,
				'SITENAME'      => $nl_config['sitename'],
				'INIT_PASS_URL' => wan_build_url('login.php?mode=cp')
			));
			$message = $tpl->pparse('mail', true);

			$email = new Email();
			$email->setFrom($admindata['admin_email'], $admindata['admin_login']);
			$email->addRecipient($new_email);
			$email->setSubject(sprintf($lang['Subject_email']['New_admin'], $nl_config['sitename']));
			$email->setTextBody($message);

			try {
				wan_sendmail($email);
			}
			catch (\Exception $e) {
				trigger_error(sprintf($lang['Message']['Failed_sending'],
					htmlspecialchars($e->getMessage())
				), E_USER_ERROR);
			}

			$output->redirect('./admin.php', 6);
			$output->addLine($lang['Message']['Admin_added']);
			$output->addLine($lang['Click_return_profile'], './admin.php?uid=' . $admin_id);
			$output->addLine($lang['Click_return_index'], './index.php');
			$output->displayMessage();
		}
	}

	$output->page_header();

	$output->set_filenames(array(
		'body' => 'add_admin_body.tpl'
	));

	$output->assign_vars(array(
		'L_TITLE'         => $lang['Add_user'],
		'L_EXPLAIN'       => nl2br($lang['Explain']['admin']),
		'L_LOGIN'         => $lang['Login'],
		'L_EMAIL'         => $lang['Email_address'],
		'L_VALID_BUTTON'  => $lang['Button']['valid'],
		'L_CANCEL_BUTTON' => $lang['Button']['cancel'],

		'LOGIN' => htmlspecialchars($new_login),
		'EMAIL' => htmlspecialchars($new_email)
	));

	$output->pparse('body');

	$output->page_footer();
}
else if ($mode == 'deluser') {
	if ($admindata['admin_id'] == $admin_id) {
		$output->displayMessage('Owner_account');
	}

	if (isset($_POST['confirm'])) {
		$db->beginTransaction();
		$db->query("DELETE FROM " . ADMIN_TABLE . " WHERE admin_id = " . $admin_id);
		$db->query("DELETE FROM " . AUTH_ADMIN_TABLE . " WHERE admin_id = " . $admin_id);
		$db->commit();

		//
		// Optimisation des tables
		//
		$db->vacuum(array(ADMIN_TABLE, AUTH_ADMIN_TABLE));

		$output->redirect('./admin.php', 6);
		$output->addLine($lang['Message']['Admin_deleted']);
		$output->addLine($lang['Click_return_profile'], './admin.php');
		$output->addLine($lang['Click_return_index'], './index.php');
		$output->displayMessage();
	}
	else {
		$output->addHiddenField('uid', $admin_id);

		$output->page_header();

		$output->set_filenames(array(
			'body' => 'confirm_body.tpl'
		));

		$output->assign_vars(array(
			'L_CONFIRM' => $lang['Title']['confirm'],

			'TEXTE' => $lang['Confirm_del_user'],
			'L_YES' => $lang['Yes'],
			'L_NO'  => $lang['No'],

			'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
			'U_FORM' => 'admin.php?mode=deluser'
		));

		$output->pparse('body');

		$output->page_footer();
	}
}

if (isset($_POST['submit'])) {
	if (!wan_is_admin($admindata) && $admin_id != $admindata['admin_id']) {
		http_response_code(401);
		$output->redirect('./index.php', 4);
		$output->addLine($lang['Message']['Not_authorized']);
		$output->addLine($lang['Click_return_index'], './index.php');
		$output->displayMessage();
	}

	$vararray = array('current_passwd', 'new_passwd', 'confirm_passwd', 'email', 'date_format', 'language');
	foreach ($vararray as $varname) {
		${$varname} = trim(u::filter_input(INPUT_POST, $varname));
	}

	if ($date_format == '') {
		$date_format = $nl_config['date_format'];
	}

	if ($language == '' || !validate_lang($language)) {
		$language = $nl_config['language'];
	}

	$email_new_subscribe = (bool) filter_input(INPUT_POST, 'email_new_subscribe', FILTER_VALIDATE_BOOLEAN);
	$email_unsubscribe   = (bool) filter_input(INPUT_POST, 'email_unsubscribe', FILTER_VALIDATE_BOOLEAN);

	$set_password = false;
	if ($new_passwd != '') {
		$set_password = true;

		if ($admin_id == $admindata['admin_id'] && !password_verify($current_passwd, $admindata['admin_pwd'])) {
			$error = true;
			$msg_error[] = $lang['Message']['Error_login'];
		}
		else if (!validate_pass($new_passwd)) {
			$error = true;
			$msg_error[] = $lang['Message']['Alphanum_pass'];
		}
		else if ($new_passwd !== $confirm_passwd) {
			$error = true;
			$msg_error[] = $lang['Message']['Bad_confirm_pass'];
		}
	}

	if (!Mailer::checkMailSyntax($email)) {
		$error = true;
		$msg_error[] = $lang['Message']['Invalid_email'];
	}

	if (!$error) {
		$sql_data = array(
			'admin_email'         => $email,
			'admin_dateformat'    => $date_format,
			'admin_lang'          => $language,
			'email_new_subscribe' => $email_new_subscribe,
			'email_unsubscribe'   => $email_unsubscribe
		);

		if ($set_password) {
			if (!($passwd_hash = password_hash($new_passwd, PASSWORD_DEFAULT))) {
				trigger_error("Unexpected error returned by password API", E_USER_ERROR);
			}
			$sql_data['admin_pwd'] = $passwd_hash;
		}

		if (wan_is_admin($admindata) && $admin_id != $admindata['admin_id']) {
			$admin_level = filter_input(INPUT_POST, 'admin_level', FILTER_VALIDATE_INT);

			if (is_int($admin_level) && in_array($admin_level, array(ADMIN_LEVEL,USER_LEVEL))) {
				$sql_data['admin_level'] = $admin_level;
			}
		}

		$db->update(ADMIN_TABLE, $sql_data, array('admin_id' => $admin_id));

		if (wan_is_admin($admindata)) {
			$auth_data = ($admindata['admin_id'] == $admin_id) ? $auth->listdata : $auth->read_data($admin_id);
			$liste_ids = (array) filter_input(INPUT_POST, 'liste_id',
				FILTER_VALIDATE_INT,
				FILTER_REQUIRE_ARRAY
			);
			$liste_ids = array_filter($liste_ids);

			$auth_post = array();
			foreach ($auth->auth_ary as $auth_name) {
				$auth_post[$auth_name] = (array) filter_input(INPUT_POST, $auth_name,
					FILTER_VALIDATE_BOOLEAN,
					FILTER_REQUIRE_ARRAY
				);
			}

			for ($i = 0, $total_liste = count($liste_ids); $i < $total_liste; $i++) {
				$sql_data = array();

				foreach ($auth->auth_ary as $auth_name) {
					$sql_data[$auth_name] = (isset($auth_post[$auth_name][$i]))
						? $auth_post[$auth_name][$i] : false;
				}

				if (!isset($auth_data[$liste_ids[$i]]['auth_view'])) {
					$sql_data['admin_id'] = $admin_id;
					$sql_data['liste_id'] = $liste_ids[$i];

					$db->insert(AUTH_ADMIN_TABLE, $sql_data);
				}
				else {
					$sql_where = array('admin_id' => $admin_id, 'liste_id' => $liste_ids[$i]);
					$db->update(AUTH_ADMIN_TABLE, $sql_data, $sql_where);
				}
			}
		}

		$output->redirect('./admin.php', 6);
		$output->addLine($lang['Message']['Profile_updated']);
		$output->addLine($lang['Click_return_profile'], './admin.php?uid=' . $admin_id);
		$output->addLine($lang['Click_return_index'], './index.php');
		$output->displayMessage();
	}
}

if (wan_is_admin($admindata)) {
	$current_admin = null;

	$admin_id = filter_input(INPUT_GET, 'uid', FILTER_VALIDATE_INT);

	if (is_int($admin_id) && $admin_id != $admindata['admin_id']) {
		$sql = "SELECT  admin_id, admin_login, admin_pwd, admin_email, admin_lang,
				admin_dateformat, admin_level, email_new_subscribe, email_unsubscribe
			FROM " . ADMIN_TABLE . "
			WHERE admin_id = " . $admin_id;
		$result = $db->query($sql);

		if (!($current_admin = $result->fetch())) {
			trigger_error("Impossible de récupérer les données de l'utilisateur", E_USER_ERROR);
		}
	}

	if (!is_array($current_admin)) {
		$current_admin = $admindata;
	}

	$sql = "SELECT admin_id, admin_login
		FROM " . ADMIN_TABLE . "
		WHERE admin_id <> $current_admin[admin_id]
		ORDER BY admin_login ASC";
	$result = $db->query($sql);

	$admin_box = '';
	if ($row = $result->fetch()) {
		$admin_box  = '<select id="uid" name="uid">';
		$admin_box .= '<option value="0">' . $lang['Choice_user'] . '</option>';

		do {
			$admin_box .= sprintf("<option value=\"%d\">%s</option>\n\t", $row['admin_id'], htmlspecialchars($row['admin_login'], ENT_NOQUOTES));
		}
		while ($row = $result->fetch());

		$admin_box .= '</select>';
	}

	if ($current_admin['admin_id'] != $admindata['admin_id']) {
		$listdata = $auth->read_data($current_admin['admin_id']);
	}
	else {
		$listdata = $auth->listdata;
	}
}
else {
	$current_admin = $admindata;
	$admin_box = '';
}

require WA_ROOTDIR . '/includes/functions.box.php';

$output->addHiddenField('uid', $current_admin['admin_id']);

if (wan_is_admin($admindata)) {
	$output->addLink('subsection', './admin.php?mode=adduser', $lang['Add_user']);
}

$output->page_header();

$output->set_filenames( array(
	'body' => 'admin_body.tpl'
));

$output->assign_vars(array(
	'L_TITLE'               => sprintf($lang['Title']['profile'], htmlspecialchars($current_admin['admin_login'], ENT_NOQUOTES)),
	'L_EXPLAIN'             => nl2br($lang['Explain']['admin']),
	'L_DEFAULT_LANG'        => $lang['Default_lang'],
	'L_EMAIL'               => $lang['Email_address'],
	'L_DATE_FORMAT'         => $lang['Dateformat'],
	'L_NOTE_DATE'           => sprintf($lang['Fct_date'], '<a href="http://www.php.net/date">', '</a>'),
	'L_EMAIL_NEW_SUBSCRIBE' => $lang['Email_new_subscribe'],
	'L_EMAIL_UNSUBSCRIBE'   => $lang['Email_unsubscribe'],
	'L_PASSWD'              => $lang['Password'],
	'L_NEW_PASSWD'          => $lang['New_passwd'],
	'L_CONFIRM_PASSWD'      => $lang['Confirm_passwd'],
	'L_NOTE_PASSWD'         => nl2br($lang['Note_passwd']),
	'L_YES'                 => $lang['Yes'],
	'L_NO'                  => $lang['No'],
	'L_VALID_BUTTON'        => $lang['Button']['valid'],
	'L_RESET_BUTTON'        => $lang['Button']['reset'],
	'L_RESTORE_DEFAULT'     => $lang['Restore_default'],

	'LANG_BOX'              => lang_box($current_admin['admin_lang']),
	'EMAIL'                 => $current_admin['admin_email'],
	'DATE_FORMAT'           => $current_admin['admin_dateformat'],
	'DEFAULT_DATE_FORMAT'   => DEFAULT_DATE_FORMAT,

	'EMAIL_NEW_SUBSCRIBE_YES' => $output->getBoolAttr('checked', ($current_admin['email_new_subscribe'] == SUBSCRIBE_NOTIFY_YES)),
	'EMAIL_NEW_SUBSCRIBE_NO'  => $output->getBoolAttr('checked', ($current_admin['email_new_subscribe'] == SUBSCRIBE_NOTIFY_NO)),

	'EMAIL_UNSUBSCRIBE_YES' => $output->getBoolAttr('checked', ($current_admin['email_unsubscribe'] == UNSUBSCRIBE_NOTIFY_YES)),
	'EMAIL_UNSUBSCRIBE_NO'  => $output->getBoolAttr('checked', ($current_admin['email_unsubscribe'] == UNSUBSCRIBE_NOTIFY_NO)),

	'S_HIDDEN_FIELDS'       => $output->getHiddenFields()
));

if (wan_is_admin($admindata)) {
	$output->assign_block_vars('admin_options', array(
		'L_ADD_ADMIN'     => $lang['Add_user'],
		'L_TITLE_MANAGE'  => $lang['Title']['manage'],
		'L_TITLE_OPTIONS' => $lang['Title']['other_options'],
		'L_ADMIN_LEVEL'   => $lang['User_level'],
		'L_LISTE_NAME'    => $lang['Liste_name2'],
		'L_VIEW'          => $lang['View'],
		'L_EDIT'          => $lang['Edit'],
		'L_DEL'           => $lang['Button']['delete'],
		'L_SEND'          => $lang['Button']['send'],
		'L_IMPORT'        => $lang['Import'],
		'L_EXPORT'        => $lang['Export'],
		'L_BAN'           => $lang['Ban'],
		'L_ATTACH'        => $lang['Attach'],
		'L_ADMIN'         => $lang['Admin'],
		'L_USER'          => $lang['User'],
		'L_DELETE_ADMIN'  => $lang['Del_user'],
		'L_NOTE_DELETE'   => nl2br($lang['Del_note']),

		'SELECTED_ADMIN'  => $output->getBoolAttr('selected', wan_is_admin($current_admin)),
		'SELECTED_USER'   => $output->getBoolAttr('selected', !wan_is_admin($current_admin))
	));

	foreach ($listdata as $listrow) {
		$output->assign_block_vars('admin_options.auth', array(
			'LISTE_NAME'      => htmlspecialchars($listrow['liste_name']),
			'LISTE_ID'        => $listrow['liste_id'],

			'BOX_AUTH_VIEW'   => $auth->box_auth(Auth::VIEW,   $listrow),
			'BOX_AUTH_EDIT'   => $auth->box_auth(Auth::EDIT,   $listrow),
			'BOX_AUTH_DEL'    => $auth->box_auth(Auth::DEL,    $listrow),
			'BOX_AUTH_SEND'   => $auth->box_auth(Auth::SEND,   $listrow),
			'BOX_AUTH_IMPORT' => $auth->box_auth(Auth::IMPORT, $listrow),
			'BOX_AUTH_EXPORT' => $auth->box_auth(Auth::EXPORT, $listrow),
			'BOX_AUTH_BACKUP' => $auth->box_auth(Auth::BAN,    $listrow),
			'BOX_AUTH_ATTACH' => $auth->box_auth(Auth::ATTACH, $listrow)
		));
	}

	if ($admin_box != '') {
		$output->assign_block_vars('admin_box', array(
			'L_VIEW_PROFILE'  => $lang['View_profile'],
			'L_BUTTON_GO'     => $lang['Button']['go'],

			'ADMIN_BOX'       => $admin_box
		));
	}
}

if ($current_admin['admin_id'] == $admindata['admin_id']) {
	$output->assign_block_vars('owner_profil', array());
}

$output->pparse('body');

$output->page_footer();
