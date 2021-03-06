--
-- Schéma des tables de WAnewsletter pour SQLite
--

--
-- Structure de la table "wa_abo_liste"
--
CREATE TABLE wa_abo_liste (
	abo_id        INTEGER  NOT NULL DEFAULT 0,
	liste_id      INTEGER  NOT NULL DEFAULT 0,
	format        INTEGER  NOT NULL DEFAULT 0,
	send          INTEGER  NOT NULL DEFAULT 0,
	register_key  CHAR(20),
	register_date INTEGER  NOT NULL DEFAULT 0,
	confirmed     INTEGER  NOT NULL DEFAULT 0,
	CONSTRAINT wa_abo_liste_pk PRIMARY KEY (abo_id, liste_id),
	CONSTRAINT register_key_idx UNIQUE (register_key)
);


--
-- Structure de la table "wa_abonnes"
--
CREATE TABLE wa_abonnes (
	abo_id     INTEGER      NOT NULL,
	abo_pseudo VARCHAR(30)  NOT NULL DEFAULT '',
	abo_pwd    VARCHAR(255) NOT NULL DEFAULT '',
	abo_email  VARCHAR(254) NOT NULL DEFAULT '',
	abo_lang   VARCHAR(30)  NOT NULL DEFAULT '',
	abo_status INTEGER      NOT NULL DEFAULT 0,
	CONSTRAINT wa_abonnes_pk PRIMARY KEY (abo_id),
	CONSTRAINT abo_email_idx UNIQUE (abo_email)
);
CREATE INDEX abo_status_idx ON wa_abonnes (abo_status);


--
-- Structure de la table "wa_admin"
--
CREATE TABLE wa_admin (
	admin_id            INTEGER      NOT NULL,
	admin_login         VARCHAR(30)  NOT NULL DEFAULT '',
	admin_pwd           VARCHAR(255) NOT NULL DEFAULT '',
	admin_email         VARCHAR(254) NOT NULL DEFAULT '',
	admin_lang          VARCHAR(30)  NOT NULL DEFAULT '',
	admin_dateformat    VARCHAR(20)  NOT NULL DEFAULT '',
	admin_level         INTEGER      NOT NULL DEFAULT 1,
	email_new_subscribe INTEGER      NOT NULL DEFAULT 0,
	email_unsubscribe   INTEGER      NOT NULL DEFAULT 0,
	html_editor         INTEGER      NOT NULL DEFAULT 1,
	CONSTRAINT wa_admin_pk PRIMARY KEY (admin_id),
	CONSTRAINT admin_login_idx UNIQUE (admin_login)
);


--
-- Structure de la table "wa_auth_admin"
--
CREATE TABLE wa_auth_admin (
	admin_id    INTEGER NOT NULL DEFAULT 0,
	liste_id    INTEGER NOT NULL DEFAULT 0,
	auth_view   INTEGER NOT NULL DEFAULT 0,
	auth_edit   INTEGER NOT NULL DEFAULT 0,
	auth_del    INTEGER NOT NULL DEFAULT 0,
	auth_send   INTEGER NOT NULL DEFAULT 0,
	auth_import INTEGER NOT NULL DEFAULT 0,
	auth_export INTEGER NOT NULL DEFAULT 0,
	auth_ban    INTEGER NOT NULL DEFAULT 0,
	auth_attach INTEGER NOT NULL DEFAULT 0,
	CONSTRAINT wa_auth_admin_pk PRIMARY KEY (admin_id, liste_id)
);


--
-- Structure de la table "wa_ban_list"
--
CREATE TABLE wa_ban_list (
	ban_id    INTEGER      NOT NULL,
	liste_id  INTEGER      NOT NULL DEFAULT 0,
	ban_email VARCHAR(254) NOT NULL DEFAULT '',
	CONSTRAINT wa_ban_list_pk PRIMARY KEY (ban_id)
);


--
-- Structure de la table "wa_config"
--
CREATE TABLE wa_config (
	config_id     INTEGER      NOT NULL,
	config_name   VARCHAR(255),
	config_value  VARCHAR(255),
	CONSTRAINT wa_config_pk PRIMARY KEY (config_id),
	CONSTRAINT config_name_idx UNIQUE (config_name)
);


--
-- Structure de la table "wa_forbidden_ext"
--
CREATE TABLE wa_forbidden_ext (
	fe_id    INTEGER     NOT NULL,
	liste_id INTEGER     NOT NULL DEFAULT 0,
	fe_ext   VARCHAR(10) NOT NULL DEFAULT '',
	CONSTRAINT wa_forbidden_ext_pk PRIMARY KEY (fe_id)
);


--
-- Structure de la table "wa_joined_files"
--
CREATE TABLE wa_joined_files (
	file_id            INTEGER      NOT NULL,
	file_real_name     VARCHAR(200) NOT NULL DEFAULT '',
	file_physical_name VARCHAR(200) NOT NULL DEFAULT '',
	file_size          INTEGER      NOT NULL DEFAULT 0,
	file_mimetype      VARCHAR(100) NOT NULL DEFAULT '',
	CONSTRAINT wa_joined_files_pk PRIMARY KEY (file_id)
);


--
-- Structure de la table "wa_liste"
--
CREATE TABLE wa_liste (
	liste_id          INTEGER      NOT NULL,
	liste_name        VARCHAR(100) NOT NULL DEFAULT '',
	liste_public      INTEGER      NOT NULL DEFAULT 1,
	liste_format      INTEGER      NOT NULL DEFAULT 1,
	sender_email      VARCHAR(254) NOT NULL DEFAULT '',
	return_email      VARCHAR(254) NOT NULL DEFAULT '',
	confirm_subscribe INTEGER      NOT NULL DEFAULT 0,
	limitevalidate    INTEGER      NOT NULL DEFAULT 3,
	form_url          VARCHAR(255) NOT NULL DEFAULT '',
	liste_sig         TEXT,
	auto_purge        INTEGER      NOT NULL DEFAULT 0,
	purge_freq        INTEGER      NOT NULL DEFAULT 0,
	purge_next        INTEGER      NOT NULL DEFAULT 0,
	liste_startdate   INTEGER      NOT NULL DEFAULT 0,
	liste_alias       VARCHAR(254) NOT NULL DEFAULT '',
	liste_numlogs     INTEGER      NOT NULL DEFAULT 0,
	use_cron          INTEGER      NOT NULL DEFAULT 0,
	pop_host          VARCHAR(100) NOT NULL DEFAULT '',
	pop_port          INTEGER      NOT NULL DEFAULT 110,
	pop_user          VARCHAR(100) NOT NULL DEFAULT '',
	pop_pass          VARCHAR(100) NOT NULL DEFAULT '',
	pop_tls           INTEGER      NOT NULL DEFAULT 0,
	CONSTRAINT wa_liste_pk PRIMARY KEY (liste_id)
);


--
-- Structure de la table "wa_log"
--
CREATE TABLE wa_log (
	log_id        INTEGER      NOT NULL,
	liste_id      INTEGER      NOT NULL DEFAULT 0,
	log_subject   VARCHAR(100) NOT NULL DEFAULT '',
	log_body_html TEXT,
	log_body_text TEXT,
	log_date      INTEGER      NOT NULL DEFAULT 0,
	log_status    INTEGER      NOT NULL DEFAULT 0,
	log_numdest   INTEGER      NOT NULL DEFAULT 0,
	CONSTRAINT wa_log_pk PRIMARY KEY (log_id)
);
CREATE INDEX liste_id_idx ON wa_log (liste_id);
CREATE INDEX log_status_idx ON wa_log (log_status);


--
-- Structure de la table "wa_log_files"
--
CREATE TABLE wa_log_files (
	log_id  INTEGER NOT NULL DEFAULT 0,
	file_id INTEGER NOT NULL DEFAULT 0,
	CONSTRAINT wa_log_files_pk PRIMARY KEY (log_id, file_id)
);


--
-- Structure de la table "wa_session"
--
CREATE TABLE wa_session (
	session_id     VARCHAR(100) NOT NULL DEFAULT '',
	session_start  INTEGER  NOT NULL DEFAULT 0,
	session_expire INTEGER  NOT NULL DEFAULT 0,
	session_data   TEXT,
	CONSTRAINT wa_session_pk PRIMARY KEY (session_id)
);

