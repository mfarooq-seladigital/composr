<form title="{!PER_PAGE}" action="{$URL_FOR_GET_FORM*,{URL}}{+START,IF_PASSED,HASH}#{HASH*}{+END}" method="get" class="inline" target="_self" autocomplete="off">
	{$SET,RAND_PAGINATION_PER_PAGE,{$RAND}}

	{HIDDEN}
	<div class="inline">
		<div class="accessibility_hidden"><label for="r_{$GET*,RAND_PAGINATION_PER_PAGE}">{!PER_PAGE}{+START,IF_NON_EMPTY,{$GET,TEXT_ID}}: {$GET*,TEXT_ID}{+END}</label></div>
		<select id="r_{$GET*,RAND_PAGINATION_PER_PAGE}" name="{MAX_NAME*}">
			{SELECTORS}
		</select><input data-disable-on-click="1" class="button_micro buttons--filter" type="submit" title="{!PER_PAGE}{+START,IF_NON_EMPTY,{$GET,TEXT_ID}}: {$GET*,TEXT_ID}{+END}" value="{!PER_PAGE}" />
	</div>
</form>
