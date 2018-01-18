<div data-toggleable-tray="{ {+START,IF_IN_ARRAY,OPTIONS,tray_open,tray_closed}save: true{+END} }" {+START,IF_NON_EMPTY,{TITLE}} id="{TITLE|}"{+END} class="box box---standardbox-default{+START,IF_PASSED,CLASS} {CLASS*}{+END}"{+START,IF_NON_EMPTY,{WIDTH}} style="width: {WIDTH*}"{+END}>
	{+START,IF_NON_EMPTY,{TITLE}}
		{+START,IF_IN_ARRAY,OPTIONS,tray_open,tray_closed}
			<h3 class="toggleable-tray-title js-tray-header">
				{+START,IF_IN_ARRAY,OPTIONS,tray_open}
					<a class="toggleable-tray-button js-tray-onclick-toggle-tray" href="#!"><img alt="{!CONTRACT}: {$STRIP_TAGS,{TITLE}}" title="{!CONTRACT}" src="{$IMG*,1x/trays/contract2}" /></a>
				{+END}
				{+START,IF_IN_ARRAY,OPTIONS,tray_closed}
					<a class="toggleable-tray-button js-tray-onclick-toggle-tray" href="#!"><img alt="{!EXPAND}: {$STRIP_TAGS,{TITLE}}" title="{!EXPAND}" src="{$IMG*,1x/trays/expand2}" /></a>
				{+END}

				{+START,IF_NON_EMPTY,{TOP_LINKS}}
					{TOP_LINKS}
				{+END}

				<a class="toggleable-tray-button js-tray-onclick-toggle-tray" href="#!">{TITLE}</a>
			</h3>
		{+END}
	{+END}

	{+START,IF_IN_ARRAY,OPTIONS,tray_open}
	<div class="toggleable-tray js-tray-content">
	{+END}
	{+START,IF_IN_ARRAY,OPTIONS,tray_closed}
	<div class="toggleable-tray js-tray-content" style="display: none" aria-expanded="false">
	{+END}
	{+START,IF_NOT_IN_ARRAY,OPTIONS,tray_open,tray_closed}
	<div class="box-inner js-tray-content">
	{+END}
		{+START,IF_NOT_IN_ARRAY,OPTIONS,tray_open,tray_closed}{+START,IF_NON_EMPTY,{TITLE}}
			<h3>{TITLE}</h3>
		{+END}{+END}

		{+START,IF_NON_EMPTY,{META}}
			<div class="meta-details" role="note">
				<dl class="meta-details-list">
					{+START,LOOP,META}
						<dt class="field-name">{KEY}:</dt> <dd>{VALUE}</dd>
					{+END}
				</dl>
			</div>
		{+END}

		{$PARAGRAPH,{CONTENT}}

		{+START,IF_NON_EMPTY,{LINKS}}
			<ul class="horizontal-links associated-links-block-group">
				{+START,LOOP,LINKS}
					<li>{_loop_var}</li>
				{+END}
			</ul>
		{+END}
	</div>
</div>
