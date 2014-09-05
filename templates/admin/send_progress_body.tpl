
<ul class="links">
	<li><a href="./envoi.php">{L_CREATE_LOG}</a></li>
	<li><a href="./envoi.php?mode=load">{L_LOAD_LOG}</a></li>
</ul>

<div class="block">
	<h2>{L_TITLE}</h2>
	
	<table class="listing">
		<tr>
			<th>{L_SUBJECT}</th>
			<th>{L_DONE}</th>
			<th colspan="2">&nbsp;</th>
		</tr>
		<!-- BEGIN logrow -->
		<tr>
			<td>{logrow.LOG_SUBJECT}</td>
			<td align="right">{logrow.SEND_PERCENT}&nbsp;%</td>
			<td nowrap><a href="./envoi.php?mode=progress&amp;id={logrow.LOG_ID}">{L_DO_SEND}</a></td>
			<td nowrap><a href="./envoi.php?mode=cancel&amp;id={logrow.LOG_ID}">{L_CANCEL_SEND}</a></td>
		</tr>
		<!-- END logrow -->
	</table>
</div>

