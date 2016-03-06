<form class="compact" method="post" action="./tools.php?mode=attach">
<div class="block">
	<h2>{L_TITLE_EXT}</h2>

	<div class="explain">{L_EXPLAIN_TO_FORBID}</div>

	<table class="dataset compact">
		<tr>
			<td><label for="ext_list">{L_FORBID_EXT}&nbsp;:</label></td>
			<td><input type="text" id="ext_list" name="ext_list" size="30" maxlength="100" /></td>
		</tr>
	</table>

	<div class="explain">{L_EXPLAIN_TO_REALLOW}</div>

	<table class="dataset compact">
		<tr>
			<td><label for="ext_ids">{L_REALLOW_EXT}&nbsp;:</label></td>
			<td>{REALLOW_EXT_BOX} </td>
		</tr>
	</table>

	<div class="bottom">
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
		<button type="reset">{L_RESET_BUTTON}</button>
	</div>
</div>
</form>
