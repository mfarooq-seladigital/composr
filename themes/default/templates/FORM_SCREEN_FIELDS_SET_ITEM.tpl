{$REQUIRE_JAVASCRIPT,core_form_interfaces}

<div class="fields-set-item" data-tpl="formScreenFieldsSetItem" data-tpl-params="{+START,PARAMS_JSON,NAME}{_*}{+END}">
	<div class="fields-set-radio">
		<label for="choose-{NAME*}"><span class="accessibility-hidden">{!CHOOSE}</span> {PRETTY_NAME*}</label>
		<input type="radio" name="{SET_NAME*}" id="choose-{NAME*}" value="{NAME*}" />

		<label for="{NAME*}">{PRETTY_NAME*}</label>

		{COMCODE}
	</div>

	<div class="mini-indent fields-set-contents vertical-alignment">
		{$SET,show_label,{$AND,{$IS_NON_EMPTY,{NAME}},{$NOT,{SKIP_LABEL}}}}
		{+START,IF,{$GET,show_label}}
			<label class="accessibility_hidden" for="{NAME*}">{PRETTY_NAME*}</label>

			<input type="hidden" name="label_for__{NAME*}" value="{$STRIP_HTML,{PRETTY_NAME*}}" />
		{+END}

		{INPUT}

		{+START,INCLUDE,FORM_SCREEN_FIELD_DESCRIPTION}{+END}

		<div id="error-{NAME*}" style="display: none" class="input-error-here">
			{+START,INCLUDE,ICON}
				NAME=status/notice
				ICON_SIZE=24
			{+END}
			<span class="js-error-message"></span>
		</div>

		<input type="hidden" id="required-posted--{NAME*}" name="require__{NAME*}" value="{$?,{REQUIRED*},1,0}" />
	</div>
</div>
