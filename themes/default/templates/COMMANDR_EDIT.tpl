<form title="{!EDIT}" action="{SUBMIT_URL*}" method="post" onsubmit="var command='write &quot;{FILE*}&quot; &quot;'+this.elements['edit_content'].value.replace(/\\/g,'\\\\').replace(/&lt;/g,'\\&lt;').replace(/&gt;/g,'\\&gt;').replace(/&quot;/g,'\\&quot;')+'&quot;'; return commandr_form_submission(command);">
	{$INSERT_SPAMMER_BLACKHOLE}

	<div>
		<p class="lonely_label"><label for="edit_content{UNIQ_ID*}">{!EDIT}:</label></p>
		<div class="constrain_field"><textarea class="textarea_scroll wide_field" cols="60" rows="10" id="edit_content{UNIQ_ID*}" name="edit_content">{FILE_CONTENTS*}</textarea></div>

		<p>
			<input class="buttons__proceed button_screen_item" type="submit" value="{!PROCEED}" />
		</p>
	</div>
</form>
