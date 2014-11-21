<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_NEWSLETTER', true);

require './pagestart.php';

$mode     = (!empty($_REQUEST['mode'])) ? $_REQUEST['mode'] : '';
$admin_id = (!empty($_REQUEST['admin_id'])) ? intval($_REQUEST['admin_id']) : 0;

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
	$new_login = (!empty($_POST['new_login'])) ? trim($_POST['new_login']) : '';
	$new_email = (!empty($_POST['new_email'])) ? trim($_POST['new_email']) : '';

	if (isset($_POST['submit'])) {
		require WA_ROOTDIR . '/includes/functions.validate.php';

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

		if (!Mailer::validate_email($new_email)) {
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

			$mailer->set_charset('UTF-8');
			$mailer->set_format(FORMAT_TEXTE);
			$mailer->set_from($admindata['admin_email'], $admindata['admin_login']);
			$mailer->set_address($new_email);
			$mailer->set_subject(sprintf($lang['Subject_email']['New_admin'], $nl_config['sitename']));

			$mailer->use_template('new_admin', array(
				'PSEUDO'        => $new_login,
				'SITENAME'      => $nl_config['sitename'],
				'INIT_PASS_URL' => wan_build_url('login.php?mode=cp')
			));

			if (!$mailer->send()) {
				trigger_error(sprintf($lang['Message']['Failed_sending2'], $mailer->msg_error), E_USER_ERROR);
			}

			$output->redirect('./admin.php', 6);
			$output->addLine($lang['Message']['Admin_added']);
			$output->addLine($lang['Click_return_profile'], './admin.php');
			$output->addLine($lang['Click_return_index'], './index.php');
			$output->displayMessage();
		}
	}

	$output->addHiddenField('mode', 'adduser');

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

		'LOGIN' => wan_htmlspecialchars($new_login),
		'EMAIL' => wan_htmlspecialchars($new_email),

		'S_HIDDEN_FIELDS' => $output->getHiddenFields()
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
		$output->addHiddenField('mode'    , 'deluser');
		$output->addHiddenField('admin_id', $admin_id);

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
			'U_FORM' => 'admin.php'
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

	$vararray = array('current_passwd', 'new_passwd', 'confirm_passwd', 'email', 'dateformat', 'language');
	foreach ($vararray as $varname) {
		${$varname} = (!empty($_POST[$varname])) ? trim($_POST[$varname]) : '';
	}

	require WA_ROOTDIR . '/includes/functions.validate.php';

	if ($dateformat == '') {
		$dateformat = $nl_config['date_format'];
	}

	if ($language == '' || !validate_lang($language)) {
		$language = $nl_config['language'];
	}

	$email_new_subscribe = (!empty($_POST['email_new_subscribe'])) ? intval($_POST['email_new_subscribe']) : SUBSCRIBE_NOTIFY_NO;
	$email_unsubscribe   = (!empty($_POST['email_unsubscribe'])) ? intval($_POST['email_unsubscribe']) : UNSUBSCRIBE_NOTIFY_NO;

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

	if (!Mailer::validate_email($email)) {
		$error = true;
		$msg_error[] = $lang['Message']['Invalid_email'];
	}

	if (!$error) {
		$sql_data = array(
			'admin_email'         => $email,
			'admin_dateformat'    => $dateformat,
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

		if (wan_is_admin($admindata) && $admin_id != $admindata['admin_id'] && !empty($_POST['admin_level'])) {
			$sql_data['admin_level'] = ($_POST['admin_level'] == ADMIN_LEVEL) ? ADMIN_LEVEL : USER_LEVEL;
		}

		$db->update(ADMIN_TABLE, $sql_data, array('admin_id' => $admin_id));

		if (wan_is_admin($admindata)) {
			$auth_data = ($admindata['admin_id'] == $admin_id) ? $auth->listdata : $auth->read_data($admin_id);
			$liste_ids = (!empty($_POST['liste_id']) && is_array($_POST['liste_id'])) ? $_POST['liste_id'] : array();

			foreach ($auth->auth_ary as $auth_name) {
				${$auth_name . '_ary'} = (!empty($_POST[$auth_name])) ? $_POST[$auth_name] : array();
			}

			for ($i = 0, $total_liste = count($liste_ids); $i < $total_liste; $i++) {
				$sql_data = array();

				foreach ($auth->auth_ary as $auth_name) {
					$sql_data[$auth_name] = ${$auth_name . '_ary'}[$i];
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
		$output->addLine($lang['Click_return_profile'], './admin.php?admin_id=' . $admin_id);
		$output->addLine($lang['Click_return_index'], './index.php');
		$output->displayMessage();
	}
}

$admin_box = '';

if (wan_is_admin($admindata)) {
	$current_admin = null;

	if (!empty($admin_id) && $admin_id != $admindata['admin_id']) {
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

	if ($row = $result->fetch()) {
		$admin_box  = '<select id="admin_id" name="admin_id">';
		$admin_box .= '<option value="0">' . $lang['Choice_user'] . '</option>';

		do {
			$admin_box .= sprintf("<option value=\"%d\">%s</option>\n\t", $row['admin_id'], wan_htmlspecialchars($row['admin_login'], ENT_NOQUOTES));
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
}

require WA_ROOTDIR . '/includes/functions.box.php';

$output->addHiddenField('admin_id', $current_admin['admin_id']);

if (wan_is_admin($admindata)) {
	$output->addLink('subsection', './admin.php?mode=adduser', $lang['Add_user']);
}

$output->page_header();

$output->set_filenames( array(
	'body' => 'admin_body.tpl'
));

$output->assign_vars(array(
	'L_TITLE'               => sprintf($lang['Title']['profile'], wan_htmlspecialchars($current_admin['admin_login'], ENT_NOQUOTES)),
	'L_EXPLAIN'             => nl2br($lang['Explain']['admin']),
	'L_DEFAULT_LANG'        => $lang['Default_lang'],
	'L_EMAIL'               => $lang['Email_address'],
	'L_DATEFORMAT'          => $lang['Dateformat'],
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

	'LANG_BOX'              => lang_box($current_admin['admin_lang']),
	'EMAIL'                 => $current_admin['admin_email'],
	'DATEFORMAT'            => $current_admin['admin_dateformat'],

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
			'LISTE_NAME'      => wan_htmlspecialchars($listrow['liste_name']),
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
