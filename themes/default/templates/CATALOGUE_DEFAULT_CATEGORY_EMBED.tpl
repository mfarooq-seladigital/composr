{+START,IF,{$NEQ,{$COMMA_LIST_GET,{BLOCK_PARAMS},raw},1}}
	{$SET,ajax_catalogue_default_category_embed_wrapper,ajax_catalogue_default_category_embed_wrapper_{$RAND%}}
	<div id="{$GET*,ajax_catalogue_default_category_embed_wrapper}" class="box-wrapper">
		{+START,SET,sorting}
			{$SET,show_sort_button,1}
			{+START,IF_NON_EMPTY,{SORTING}}
				<div class="box category-sorter inline-block"><div class="box-inner">
					{SORTING}
				</div></div>
			{+END}
		{+END}

		{+START,IF,{$THEME_OPTION,infinite_scrolling}}
			{$GET,sorting}
		{+END}

		{+START,IF_NON_EMPTY,{ENTRIES}}
			<div class="float-surrounder display-type-{DISPLAY_TYPE*} raw-ajax-grow-spot">
				{ENTRIES}
			</div>
		{+END}

		{+START,IF_EMPTY,{ENTRIES}}
			<p class="nothing-here">
				{!NO_ENTRIES,catalogue_entry}
			</p>
		{+END}

		{+START,IF,{$NOT,{$THEME_OPTION,infinite_scrolling}}}
			{$GET,sorting}
		{+END}

		{+START,IF_NON_EMPTY,{PAGINATION}}
			<div class="pagination-spacing float-surrounder ajax-block-wrapper-links">
				{PAGINATION}
			</div>

			{+START,INCLUDE,AJAX_PAGINATION}
				ALLOW_INFINITE_SCROLL={$EQ,{DISPLAY_TYPE},FIELDMAPS,GRID}
			{+END}
		{+END}
	</div>
{+END}

{+START,IF,{$EQ,{$COMMA_LIST_GET,{BLOCK_PARAMS},raw},1}}
	{ENTRIES}

	{PAGINATION}
{+END}
