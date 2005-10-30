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
	<meta name="Copyright" content="phpCodeur (c) 2002-2005" />
	<meta name="Robots" content="noindex, nofollow, none" />
	
	<link rel="stylesheet" type="text/css" href="../templates/wanewsletter.css" media="screen" title="Wanewsletter th�me" />
</head>
<body>

<div id="header">
	<p><img src="../images/logo-wa.png" width="160" height="60" alt="{PAGE_TITLE}" title="{PAGE_TITLE}" /></p>
	
	<h1>{PAGE_TITLE}</h1>
</div>

{ERROR_BOX}

<form method="post" action="./update.php">
<div id="global">
	
	<!-- BEGIN welcome -->
	<div class="bloc"><p>{welcome.L_EXPLAIN_UPDATE}</p></div>
	
	<div class="bloc">
	<h2>{PAGE_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="medrow1"> <label for="admin_login">{welcome.L_LOGIN}&#160;:</label> </td>
			<td class="medrow2"> <input type="text" id="admin_login" name="admin_login" maxlength="30" size="30" class="text" /> </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="admin_pass">{welcome.L_PASS}&#160;:</label> </td>
			<td class="medrow2"> <input type="password" id="admin_pass" name="admin_pass" maxlength="30" size="30" class="text" /> </td>
		</tr>
	</table>
	
	<div class="bottom">
		<input type="submit" name="start_update" value="{welcome.L_UPDATE_BUTTON}" class="pbutton" />
	</div>
	</div>
	<!-- END welcome -->
	
	<!-- BEGIN result -->
	<div class="bloc">
	<h2>{result.L_TITLE}</h2>
	
	<p>{result.MSG_RESULT}</p>
	</div>
	<!-- END result -->
</div>
</form>

<hr />

<address id="footer">
Powered by <a href="http://phpcodeur.net/" hreflang="fr" title="Site officiel de WAnewsletter">
phpCodeur</a> &copy; 2002&#8211;2005 | WAnewsletter {NEW_VERSION} {TRANSLATE}<br />
Ce script est distribu� librement sous <a href="http://phpcodeur.net/wascripts/GPL" hreflang="fr">
licence <acronym title="General Public Licence" xml:lang="en" lang="en">GPL</acronym></a>
</address>

</body>
</html>
