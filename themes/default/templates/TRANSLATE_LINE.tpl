{$REQUIRE_JAVASCRIPT,core_language_editing}

<tr data-tpl="translateLine" data-tpl-params="{+START,PARAMS_JSON,TRANSLATE_AUTO}{_*}{+END}">
	<th class="translate_line_first">
		<a id="jmp_{NAME*}"></a>

		<kbd>{NAME*}</kbd>

		{+START,IF_NON_EMPTY,{DESCRIPTION}}
			<p>
				{DESCRIPTION*}
			</p>
		{+END}
	</th>
	<td class="translate_line_second js-mouseover-enable-textarea-translate-field">
		<div class="accessibility-hidden"><label for="old__trans_{NAME*}">{!OLD} {NAME*}</label></div>
		<div>
			<textarea disabled="disabled" readonly="readonly" class="translate_original_text wide-field" cols="60" rows="{$ADD,{$DIV,{$LENGTH,{OLD}},80},1}" id="old__trans_{NAME*}" name="old__{NAME*}">{OLD*}</textarea>
		</div>

		<div class="arrow-ruler"><img alt="" src="{$IMG*,arrow_ruler_small}" /></div>

		<div class="accessibility-hidden"><label for="trans_{NAME*}">{NAME*}</label></div>
		<div>
			<textarea {+START,IF,{$EQ,{OLD},{CURRENT}}} disabled="disabled"{+END} class="wide-field translate_field js-textarea-translate-field {+START,IF_PASSED,TRANSLATE_AUTO}js-textarea-click-set-value{+END}" cols="60" rows="{+START,IF,{$EQ,{CURRENT},}}{$ADD,{$DIV,{$LENGTH,{OLD}},80},1}{+END}{+START,IF,{$NEQ,{CURRENT},}}{$ADD,{$DIV,{$LENGTH,{CURRENT}},80},1}{+END}" id="trans_{NAME*}" name="{NAME*}">{CURRENT*}</textarea>
		</div>
	</td>
	{+START,IF_NON_EMPTY,{ACTIONS}}
		<td>
			{ACTIONS}
		</td>
	{+END}
</tr>
<tr id="rexp_{NAME*}" style="display: none">
	<td colspan="{$?,{$IS_EMPTY,{ACTIONS}},3,4}">
		<div id="exp_{NAME*}"></div>
	</td>
</tr>
