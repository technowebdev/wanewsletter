<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--
	Copyright (c) 2002-2006 Aur�lien Maille
	
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
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{CONTENT_LANG}" lang="{CONTENT_LANG}" dir="{CONTENT_DIR}">
<head>
	<title>{PAGE_TITLE}</title>
	
	<meta name="Author" content="Bobe" />
	<meta name="Editor" content="jEdit" />
	
	<link rel="stylesheet" type="text/css" href="../templates/wanewsletter.css" media="screen" title="Wanewsletter th�me" />
	
	<script type="text/javascript">
	<!--
	var lang = [];
	lang['unused'] = 'Unused';
	
	function specialSQLite(db_box)
	{
		var fields = db_box.form.elements;
		
		if( db_box.options[db_box.selectedIndex].value == 'sqlite' ) {
			fields['host'].disabled   = true;
			fields['host'].value      = lang['unused'];
			fields['dbname'].disabled = true;
			fields['dbname'].value    = lang['unused'];
			fields['user'].disabled   = true;
			fields['user'].value      = lang['unused'];
			fields['pass'].type       = 'text';
			fields['pass'].disabled   = true;
			fields['pass'].value      = lang['unused'];
		}
		else {
			fields['host'].disabled   = false;
			fields['host'].value      = fields['host'].defaultValue;
			fields['dbname'].disabled = false;
			fields['dbname'].value    = fields['dbname'].defaultValue;
			fields['user'].disabled   = false;
			fields['user'].value      = fields['user'].defaultValue;
			fields['pass'].type       = 'password';
			fields['pass'].disabled   = false;
			fields['pass'].value      = fields['pass'].defaultValue;
		}
	}
	
	window.onload = function() {
		var SQLiteBox;
		if( (SQLiteBox = document.getElementById('driver')) != null ) {
			specialSQLite(SQLiteBox);
		}
	};
	//-->
	</script>
</head>
<body>

<div id="header">
	<div id="logo">
		<img src="../images/logo-wa.png" width="160" height="60" alt="{PAGE_TITLE}" title="{PAGE_TITLE}" />
	</div>
	
	<h1>{PAGE_TITLE}</h1>
</div>

{ERROR_BOX}

<form method="post" action="./install.php">
<div id="global">
	
	<!-- BEGIN install -->
	<div class="bloc"><p>{install.L_EXPLAIN}</p></div>
	
	<div class="bloc">
	<h2>{install.TITLE_DATABASE}</h2>
	
	<table class="content">
		<tr>
			<td class="medrow1"><label for="driver">{install.L_DBTYPE}&#160;:</label></td>
			<td class="medrow2"><select id="driver" name="driver" onchange="specialSQLite(this);">{install.DB_BOX}</select></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="host">{install.L_DBHOST}&#160;:</label></td>
			<td class="medrow2"><input type="text" id="host" name="host" size="30" value="{install.DBHOST}" class="text" /></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="dbname">{install.L_DBNAME}&#160;:</label></td>
			<td class="medrow2"><input type="text" id="dbname" name="dbname" size="30" value="{install.DBNAME}" class="text" /></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="user">{install.L_DBUSER}&#160;:</label></td>
			<td class="medrow2"><input type="text" id="user" name="user" size="30" value="{install.DBUSER}" class="text" /></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="pass">{install.L_DBPWD}&#160;:</label></td>
			<td class="medrow2"><input type="password" id="pass" name="pass" size="30" class="text" /></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="prefixe">{install.L_PREFIXE}&#160;:</label></td>
			<td class="medrow2"><input type="text" id="prefixe" name="prefixe" size="10" value="{install.PREFIXE}" class="text" /></td>
		</tr>
	</table>
	
	<h2>{install.TITLE_ADMIN}</h2>
	
	<table class="content">
		<tr>
			<td class="medrow1"><label for="language">{install.L_DEFAULT_LANG}&#160;:</label></td>
			<td class="medrow2">{install.LANG_BOX}</td>
		</tr>
		<tr>
			<td class="medrow1"><label for="admin_login">{install.L_LOGIN}&#160;:</label></td>
			<td class="medrow2"><input type="text" id="admin_login" name="admin_login" size="30" value="{install.LOGIN}" maxlength="30" class="text" /></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="admin_pass">{install.L_PASS}&#160;:</label></td>
			<td class="medrow2"><input type="password" id="admin_pass" name="admin_pass" size="25" maxlength="30" class="text" /></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="confirm_pass">{install.L_PASS_CONF}&#160;:</label></td>
			<td class="medrow2"><input type="password" id="confirm_pass" name="confirm_pass" size="25" maxlength="30" class="text" /></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="admin_email">{install.L_EMAIL}&#160;:</label></td>
			<td class="medrow2"><input type="text" id="admin_email" name="admin_email" size="30" value="{install.EMAIL}" class="text" /></td>
		</tr>
	</table>
	
	<h2>{install.TITLE_DIVERS}</h2>
	
	<table class="content">
		<tr>
			<td class="medrow1"><label for="urlsite">{install.L_URLSITE}&#160;:</label><br /><span class="m-texte">{L_URLSITE_NOTE}</span></td>
			<td class="medrow2"><input type="text" id="urlsite" name="urlsite" size="30" value="{install.URLSITE}" maxlength="100" class="text" /></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="urlscript">{install.L_URLSCRIPT}&#160;:</label><br /><span class="m-texte">{L_URLSCRIPT_NOTE}</span></td>
			<td class="medrow2"><input type="text" id="urlscript" name="urlscript" size="30" value="{install.URLSCRIPT}" maxlength="100" class="text" /></td>
		</tr>
	</table>
	
	<div class="bottom">
		<input type="submit" name="start" value="{install.L_START_BUTTON}" class="pbutton" />
	</div>
	
	</div>
	<!-- END welcome -->
	
	<!-- BEGIN reinstall -->
	<div class="bloc"><p>{reinstall.L_EXPLAIN}</p></div>
	
	<div class="bloc">
	<h2>{PAGE_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="medrow1"><label for="admin_login">{reinstall.L_LOGIN}&#160;:</label></td>
			<td class="medrow2"><input type="text" id="admin_login" name="admin_login" value="{reinstall.LOGIN}" maxlength="30" size="30" class="text" /></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="admin_pass">{reinstall.L_PASS}&#160;:</label> </td>
			<td class="medrow2"><input type="password" id="admin_pass" name="admin_pass" maxlength="30" size="30" class="text" /></td>
		</tr>
	</table>
	
	<div class="bottom">
		<input type="submit" name="start" value="{reinstall.L_START_BUTTON}" class="pbutton" />
	</div>
	</div>
	<!-- END reinstall -->
	
	<!-- BEGIN download_file -->
	<div class="bloc">
	<h2>{download_file.L_TITLE}</h2>
	
	<p>{download_file.MSG_RESULT}</p>
	
	<div class="bottom"> {download_file.S_HIDDEN_FIELDS}
		<input type="submit" name="sendfile" value="{download_file.L_DL_BUTTON}" class="pbutton" />
	</div>
	</div>
	<!-- END download_file -->
	
	<input type="hidden" name="prev_language" value="{S_PREV_LANGUAGE}" />
</div>
</form>

<hr />

<address id="footer">
Powered by <a href="http://phpcodeur.net/" hreflang="fr" title="Site officiel de Wanewsletter">
phpCodeur</a> &copy; 2002&#8211;2006 | Wanewsletter {NEW_VERSION} {TRANSLATE}<br />
Ce script est distribu� librement sous <a href="http://phpcodeur.net/wascripts/GPL" hreflang="fr">
licence <abbr title="General Public Licence" xml:lang="en" lang="en">GPL</abbr></a>
</address>

</body>
</html>
