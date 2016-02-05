<!DOCTYPE html>
<html lang="{CONTENT_LANG}" dir="{CONTENT_DIR}">
<head>
	<meta charset="UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="Robots" content="noindex, nofollow, none" />
	{META}

	<title>{PAGE_TITLE}</title>

	<link rel="stylesheet" href="{BASEDIR}/templates/wanewsletter.css" />

	{S_NAV_LINKS}

	<script src="{BASEDIR}/templates/admin/admin.js"></script>
	{S_SCRIPTS}
	<script>
	<!--
	var lang = [];
	lang['restore-default'] = '{L_RESTORE_DEFAULT}';
	// Vite fait mal fait. On fera un truc propre plus tard.
	var basedir = '{BASEDIR}';
	//-->
	</script>
</head>
<body id="top">

<div id="header">
	<div id="logo">
		<a href="./index.php">
			<img src="{BASEDIR}/images/logo-wa.png" width="160" height="60" alt="{L_INDEX}" title="{L_INDEX}" />
		</a>
	</div>

	<h1>{SITENAME}</h1>
</div>

<ul id="menu">
	<li><a href="./login.php?mode=logout">{L_LOGOUT}</a></li>
	<li><a href="./config.php">{L_CONFIG}</a></li>
	<li><a href="./envoi.php">{L_SEND}</a></li>
	<li><a href="./view.php?mode=abonnes">{L_SUBSCRIBERS}</a></li>
	<li><a href="./view.php?mode=liste">{L_LIST}</a></li>
	<li><a href="./view.php?mode=log">{L_LOG}</a></li>
	<li><a href="./tools.php">{L_TOOLS}</a></li>
	<li><a href="./admin.php">{L_USERS}</a></li>
	<li><a href="./stats.php">{L_STATS}</a></li>
</ul>

<div id="global">

{ERROR_BOX}
