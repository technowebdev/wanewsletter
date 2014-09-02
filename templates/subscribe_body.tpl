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
<html lang="fr">
<head>
	<meta charset="ISO-8859-1" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	
	<title>{PAGE_TITLE}</title>
	
	<style>
	body { font: .8em "Bitstream Vera Sans", Verdana, Arial, sans-serif; }
	
	form#subscribe-form { width: 60%; margin: 30px auto 15px; }
	form#subscribe-form fieldset	   { border: 1px dashed #79C; padding: 10px; }
	form#subscribe-form legend		   { background-color: white; padding: 1px 4px; color: black; }
	form#subscribe-form div			   { padding: 5px 8px; }
	form#subscribe-form div.bloc label { display: block; float: left; width: 30%; margin-top: .2em; cursor: pointer; }
	form#subscribe-form div label	   { cursor: pointer; }
	form#subscribe-form div.center	   { text-align: center; }
	form#subscribe-form p.message	   { text-align: center; }
	
	form#subscribe-form select,
	form#subscribe-form input[type="text"]  { border: 1px inset silver; }
	
	abbr[title] { cursor: help; }
	address#footer {
		margin: 15px auto;
		text-align: center;
		font-style: normal;
		font-size: 11px;
	}
	</style>
	
	<script>
	<!--
	var submitted = false;
	
	function check_form(evt)
	{
		var emailAddr   = document.forms['subscribe-form'].elements['email'].value;
		var cancelEvent = null;
		
		if( emailAddr.indexOf('@', 1) == -1 ) {// Test tr�s basique pour �viter un traitement superflu du formulaire
			window.alert('{L_INVALID_EMAIL}');
			cancelEvent = true;
		}
		else if( submitted == true ) {
			window.alert('{L_PAGE_LOADING}');
			cancelEvent = true;
		}
		else {
			submitted = true;
		}
		
		if( cancelEvent == true ) {
			if( evt && typeof(evt.preventDefault) != 'undefined' ) { // standard
				evt.preventDefault();
			}
			else { // MS
				window.event.returnValue = false;
			}
		}
	}
	
	window.onload = function() {
		document.forms['subscribe-form'].onsubmit = check_form;
	}
	//-->
	</script>
</head>
<body>

<form id="subscribe-form" method="post" action="./subscribe.php">
<fieldset>
	<legend lang="en">Mailing liste</legend>
	
	<div class="bloc">
		<label for="email">{L_EMAIL}&nbsp;:</label>
		<input type="text" id="email" name="email" size="25" maxlength="250" />
	</div>
	
	<div class="bloc">
		<label for="format">{L_FORMAT}&nbsp;:</label>
		<select id="format" name="format"><option value="1">TXT</option><option value="2">HTML</option></select>
	</div>
	
	<div class="bloc">
		<label for="liste">{L_DIFF_LIST}&nbsp;:</label>
		{LIST_BOX}
	</div>
	
	<div class="center">
		<label><input type="radio" name="action" value="inscription" checked="checked" /> {L_SUBSCRIBE}</label>
		<label><input type="radio" name="action" value="setformat" /> {L_SETFORMAT}</label>
		<label><input type="radio" name="action" value="desinscription" /> {L_UNSUBSCRIBE}</label>
	</div>
	
	<p class="message">{MESSAGE}</p>
	
	<div class="center"><input type="submit" name="wanewsletter" value="{L_VALID_BUTTON}" /></div>
</fieldset>
</form>

<address id="footer">
Powered by <a href="http://phpcodeur.net/" hreflang="fr" title="Site officiel de Wanewsletter">
phpCodeur</a> &copy; 2002&ndash;2014 | Wanewsletter<br />
Ce script est distribu� librement sous <a href="http://phpcodeur.net/wascripts/GPL" hreflang="fr">
licence <abbr title="General Public Licence" lang="en">GPL</abbr></a>
</address>

</body>
</html>
