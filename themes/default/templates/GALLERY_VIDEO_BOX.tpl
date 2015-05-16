<section class="box box___gallery_video_box"><div class="box_inner">
	{+START,IF_NON_EMPTY,{TITLE}}
		{+START,SET,content_box_title}
			{+START,IF,{GIVE_CONTEXT}}
				{!CONTENT_IS_OF_TYPE,{!VIDEO},{TITLE*}}
			{+END}

			{+START,IF,{$NOT,{GIVE_CONTEXT}}}
				{+START,FRACTIONAL_EDITABLE,{TITLE},title,_SEARCH:cms_galleries:__edit_other:{ID},0}{TITLE*}{+END}
			{+END}
		{+END}
		{+START,IF,{$NOT,{$GET,skip_content_box_title}}}
			<h3>{$GET,content_box_title}</h3>
		{+END}
	{+END}

	<div>
		<a href="{URL*}">{THUMB}</a>
	</div>

	{+START,IF_NON_EMPTY,{BREADCRUMBS}}
		<nav class="breadcrumbs" itemprop="breadcrumb" role="navigation"><p>{!LOCATED_IN,{BREADCRUMBS}}</p></nav>
	{+END}
</div></section>
