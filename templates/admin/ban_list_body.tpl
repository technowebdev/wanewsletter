<form class="compact" method="post" action="./tools.php?mode=ban">
<div class="block">
	<h2>{L_TITLE_BAN}</h2>

	<div class="explain">{L_EXPLAIN_BAN}</div>

	<table class="dataset">
		<tr>
			<td><label for="pattern">{L_BAN_EMAIL}&nbsp;:</label></td>
			<td><input type="text" id="pattern" name="pattern" size="30" maxlength="254" /></td>
		</tr>
	</table>

	<div class="explain">{L_EXPLAIN_UNBAN}</div>

	<table class="dataset">
		<tr>
			<td><label for="unban_ids">{L_UNBAN_EMAIL}&nbsp;:</label></td>
			<td>{UNBAN_EMAIL_BOX}</td>
		</tr>
	</table>

	<div class="bottom"> {S_HIDDEN_FIELDS}
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
		<button type="reset">{L_RESET_BUTTON}</button>
	</div>
</div>
</form>
