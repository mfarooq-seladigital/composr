{$REQUIRE_JAVASCRIPT,core_form_interfaces}

<div data-tpl="formScreenInputMultiList">
	<select multiple="multiple" size="{SIZE*}" tabindex="{TABINDEX*}" class="input-list" id="{NAME*}" name="{NAME*}[]"{+START,IF,{$EQ,{SIZE},5}} data-cms-select2="{ dropdownAutoWidth: true }"{+END}>
		{CONTENT}
	</select>

	{+START,IF_PASSED,CUSTOM_NAME}
		<div class="various-ticks clearfix">
			<div class="input-other-tick">
				{+START,IF,{CUSTOM_ACCEPT_MULTIPLE}}
					{+START,LOOP,CUSTOM_VALUE}
						<div><div>
							{+START,IF,{$EQ,{_loop_key},0}}
								<label for="{CUSTOM_NAME*}-value-{_loop_key*}">{!OTHER}</label>&hellip;<br />
							{+END}
							<input id="{CUSTOM_NAME*}-value-{_loop_key*}" name="{CUSTOM_NAME*}_value[]" value="{_loop_var*}" size="15" type="text" class="form-control form-control-inline js-keypress-input-ensure-next-field" />
						</div></div>
					{+END}
				{+END}

				{+START,IF,{$NOT,{CUSTOM_ACCEPT_MULTIPLE}}}
					<div><div>
						<label for="{CUSTOM_NAME*}_value">{!OTHER}</label>
						<input id="{CUSTOM_NAME*}_value" class="form-control form-control-inline" name="{CUSTOM_NAME*}_value" value="{+START,IF_PASSED,CUSTOM_VALUE}{CUSTOM_VALUE*}{+END}" size="15" type="text" />
					</div></div>
				{+END}
			</div>
		</div>
	{+END}
</div>
