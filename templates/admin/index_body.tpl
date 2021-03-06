<!-- BEGIN check_update_js -->
<script>
<!--
document.addEventListener('DOMContentLoaded', function() {
	document.getElementById('check-update').addEventListener('click', function(evt) {
		evt.preventDefault();

		var mainBlock = document.getElementById('check-update').parentNode;
		mainBlock.replaceChild(loadingImg, document.getElementById('check-update'));

		var xhr = new XMLHttpRequest();
		xhr.onload = function() {
			var result = JSON.parse(xhr.responseText);

			var strong = document.createElement('strong');
			mainBlock.replaceChild(strong, loadingImg);

			if (result.error) {
				strong.className = 'unavailable';
			}
			else if (result.code == 1) {
				strong.className = 'available';
				mainBlock.appendChild(document.createTextNode(' \u2013 '));

				var link = document.createElement('a');
				link.setAttribute('href', '{U_DOWNLOAD_PAGE}');
				link.textContent = '{L_DOWNLOAD_PAGE}';
				mainBlock.appendChild(link);
			}

			strong.textContent = result.message;
		};
		xhr.open('GET', evt.target.href + '&output=json', true);
		xhr.send();
	}, false);

	// Image de chargement
	var loadingImg = document.createElement('img');
	loadingImg.setAttribute('src', '../templates/images/loading.gif');
	loadingImg.setAttribute('alt', 'Loading\u2026');
	loadingImg.style.verticalAlign = 'middle';
	loadingImg.style.lineHeight = '1';
}, false);
//-->
</script>
<!-- END check_update_js -->

<p id="explain">{L_EXPLAIN}</p>

<div class="block">
	<h2>{TITLE_HOME}</h2>

	<ul id="home">
		<li>{REGISTERED_SUBSCRIBERS}</li>
		<li>{TEMP_SUBSCRIBERS}</li>
		<li>{NEWSLETTERS_SENDED}</li>
		<!-- BEGIN switch_last_newsletter -->
		<li>{switch_last_newsletter.DATE_LAST_NEWSLETTER}</li>
		<!-- END switch_last_newsletter -->
		<li>{L_DBSIZE}&nbsp;: <b>{DBSIZE}</b></li>
		<li>{L_FILESIZE}&nbsp;: <b>{FILESIZE}</b></li>
		<!-- BEGIN version_info -->
		<li>{version_info.VERSION} &ndash;
			<!-- BEGIN up_to_date -->
			<strong>{version_info.up_to_date.L_UP_TO_DATE}</strong>
			<!-- END up_to_date -->
			<!-- BEGIN update_available -->
			<strong class="available">{version_info.update_available.L_UPDATE_AVAILABLE}</strong>
			&ndash; <a href="{U_DOWNLOAD_PAGE}">{L_DOWNLOAD_PAGE}</a>
			<!-- END update_available -->
			<!-- BEGIN check_update -->
			<a id="check-update" href="upgrade.php?mode=check">{version_info.check_update.L_CHECK_UPDATE}</a>
			<!-- END check_update -->
		</li>
		<!-- END version_info -->
	</ul>
</div>

{LISTBOX}

