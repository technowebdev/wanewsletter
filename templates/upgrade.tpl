<!DOCTYPE html>
<!--
	Copyright (c) 2002-2014 Aur�lien Maille
	
	Wanewsletter is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License 
	as published by the Free Software Foundation; either version 2 
	of the License, or (at your option) any later version.
	
	Wanewsletter is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with Wanewsletter; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
-->
<html lang="{CONTENT_LANG}" dir="{CONTENT_DIR}">
<head>
	<meta charset="{CHARSET}" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="Copyright" content="phpCodeur (c) 2002-2014" />
	<meta name="Robots" content="noindex, nofollow, none" />
	
	<title>{PAGE_TITLE}</title>
	
	<link rel="stylesheet" href="../templates/wanewsletter.css" />
</head>
<body>

<div id="header">
	<div id="logo">
		<img src="../images/logo-wa.png" width="160" height="60" alt="{PAGE_TITLE}" title="{PAGE_TITLE}" />
	</div>
	
	<h1>{PAGE_TITLE}</h1>
</div>

{ERROR_BOX}

<form method="post" action="./upgrade.php">
<div id="global">
	
	<!-- BEGIN upgrade -->
	<div class="block"><p>{upgrade.L_EXPLAIN}</p></div>
	
	<div class="block">
	<h2>{PAGE_TITLE}</h2>
	
	<table class="dataset compact">
		<tr>
			<td><label for="admin_login">{upgrade.L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="admin_login" name="admin_login" value="{upgrade.LOGIN}" maxlength="30" size="30" /></td>
		</tr>
		<tr>
			<td><label for="admin_pass">{upgrade.L_PASS}&nbsp;:</label></td>
			<td><input type="password" id="admin_pass" name="admin_pass" maxlength="30" size="30" /></td>
		</tr>
	</table>
	
	<div class="bottom">
		<button type="submit" name="start" class="primary">{upgrade.L_START_BUTTON}</button>
	</div>
	</div>
	<!-- END upgrade -->
	
	<!-- BEGIN download_file -->
	<div class="block">
	<h2>{download_file.L_TITLE}</h2>
	
	<p>{download_file.MSG_RESULT}</p>
	
	<div class="bottom">{download_file.S_HIDDEN_FIELDS}
		<button type="submit" name="sendfile" class="primary">{download_file.L_DL_BUTTON}</button>
	</div>
	</div>
	<!-- END download_file -->
</div>
</form>

<hr />

<address id="footer">
Powered by <a href="http://phpcodeur.net/" hreflang="fr" title="Site officiel de Wanewsletter">
phpCodeur</a> &copy; 2002&ndash;2014 | Wanewsletter {NEW_VERSION} {TRANSLATE}<br />
Ce script est distribu� librement sous <a href="http://phpcodeur.net/wascripts/GPL" hreflang="fr">
licence <abbr title="General Public Licence" lang="en">GPL</abbr></a>
</address>

</body>
</html>
