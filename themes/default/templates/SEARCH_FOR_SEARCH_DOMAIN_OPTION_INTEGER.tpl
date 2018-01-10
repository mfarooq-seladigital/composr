<div class="search-option float-surrounder">
	{+START,IF_NON_PASSED_OR_FALSE,HAS_RANGE}
		<label for="sd_{NAME*}">{DISPLAY*}:</label>

		<input size="5" maxlength="255" class="search-option-value" data-cms-invalid-pattern="[^\-\d{$BACKSLASH}{$DECIMAL_POINT*}]" type="number" id="sd_{NAME*}" name="{NAME*}" value="{SPECIAL*}" />
	{+END}

	{+START,IF_PASSED_AND_TRUE,HAS_RANGE}
		<label for="sd_{NAME*}_from">{DISPLAY*}<span class="accessibility-hidden">, {!FROM}</span>:</label>
		<label for="sd_{NAME*}_to" class="accessibility-hidden">>{DISPLAY*}, {!TO}:</label>

		<input size="5" maxlength="255" class="search-option-value" data-cms-invalid-pattern="[^\-\d{$BACKSLASH}{$DECIMAL_POINT*}]" type="number" id="sd_{NAME*}_from" name="{NAME*}_from" value="{$PREG_REPLACE*,;.*$,,{SPECIAL}}" />

		&ndash;

		<input size="5" maxlength="255" class="search-option-value"data-cms-invalid-pattern="[^\-\d{$BACKSLASH}{$DECIMAL_POINT*}]" type="number" id="sd_{NAME*}_to" name="{NAME*}_to" value="{$PREG_REPLACE*,^.*;,,{SPECIAL}}" />
	{+END}
</div>
