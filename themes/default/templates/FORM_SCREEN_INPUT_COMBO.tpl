{$REQUIRE_JAVASCRIPT,core_form_interfaces}

<div class="webstandards-checker-off inline" data-tpl="formScreenInputCombo" data-tpl-params="{+START,PARAMS_JSON,NAME}{_*}{+END}">
	<input autocomplete="off" class="input-line{REQUIRED*} js-keyup-toggle-fallback-list" tabindex="{TABINDEX*}" type="text" value="{DEFAULT*}" id="{NAME*}" name="{NAME*}" list="{NAME*}_list" />
	<datalist id="{NAME*}_list">
		<span class="associated-details">{!fields:OR_ONE_OF_THE_BELOW}:</span>
		<select size="5" name="{NAME*}" id="{NAME*}_fallback_list" class="input-list{REQUIRED*}" style="display: block; width: 14em">{$,select is for non-datalist-aware browsers}
			{CONTENT}
		</select>
	</datalist>
</div>
