--
-- Données de base de WAnewsletter
--


--
-- Création d'un compte administrateur (mot de passe par défaut: admin)
--
INSERT INTO wa_admin (admin_login, admin_pwd, admin_email, admin_lang, admin_dateformat, admin_level)
	VALUES('admin', '$2y$10$5IAy.2zYfrQ9YxUmcZWW4ubOnGwO9RdE1ci/rtkkxehOY/ehS0ylm', 'admin@domaine.com', 'fr', 'd F Y H:i', 2);
INSERT INTO wa_auth_admin (admin_id, liste_id, auth_view, auth_edit, auth_del, auth_send, auth_import, auth_export, auth_ban, auth_attach)
	VALUES (1, 1, 1, 1, 1, 1, 1, 1, 1, 1);


--
-- Configuration de base
--
INSERT INTO wa_config (config_name, config_value) VALUES('sitename',       'Yourdomaine');
INSERT INTO wa_config (config_name, config_value) VALUES('urlsite',        'http://www.yourdomaine.com');
INSERT INTO wa_config (config_name, config_value) VALUES('path',           '/');
INSERT INTO wa_config (config_name, config_value) VALUES('date_format',    'd F Y H:i');
INSERT INTO wa_config (config_name, config_value) VALUES('session_length', '3600');
INSERT INTO wa_config (config_name, config_value) VALUES('language',       'fr');
INSERT INTO wa_config (config_name, config_value) VALUES('cookie_name',    'wanewsletter');
INSERT INTO wa_config (config_name, config_value) VALUES('cookie_path',    '/');
INSERT INTO wa_config (config_name, config_value) VALUES('upload_path',    'data/uploads/');
INSERT INTO wa_config (config_name, config_value) VALUES('max_filesize',   '80000');
INSERT INTO wa_config (config_name, config_value) VALUES('engine_send',    '2');
INSERT INTO wa_config (config_name, config_value) VALUES('sending_limit',  '100');
INSERT INTO wa_config (config_name, config_value) VALUES('sending_delay',  '10');
INSERT INTO wa_config (config_name, config_value) VALUES('use_smtp',       '0');
INSERT INTO wa_config (config_name, config_value) VALUES('smtp_host',      '');
INSERT INTO wa_config (config_name, config_value) VALUES('smtp_port',      '25');
INSERT INTO wa_config (config_name, config_value) VALUES('smtp_user',      '');
INSERT INTO wa_config (config_name, config_value) VALUES('smtp_pass',      '');
INSERT INTO wa_config (config_name, config_value) VALUES('smtp_tls',       '0');
INSERT INTO wa_config (config_name, config_value) VALUES('disable_stats',  '0');
INSERT INTO wa_config (config_name, config_value) VALUES('enable_profil_cp', '0');
INSERT INTO wa_config (config_name, config_value) VALUES('mailing_startdate', '0');
INSERT INTO wa_config (config_name, config_value) VALUES('debug_level',    '1');
INSERT INTO wa_config (config_name, config_value) VALUES('db_version',     '28');


--
-- Extensions interdites par défaut
--
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'exe');
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'php');
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'php3');
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'scr');
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'pif');
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'bat');
