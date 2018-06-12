{+START,SET,PREVIEW_CONTENTS}
	{+START,IF_NON_EMPTY,{SUMMARY}}
		<div class="clearfix">
			{$TRUNCATE_LEFT,{SUMMARY},300,0,1}
		</div>
	{+END}

	{+START,IF_PASSED,BREADCRUMBS}{+START,IF_NON_EMPTY,{BREADCRUMBS}}
		<nav class="breadcrumbs" itemprop="breadcrumb"><p>
			{!LOCATED_IN,{BREADCRUMBS}}
		</p></nav>
	{+END}{+END}

	<p class="shunted-button">
		<a class="btn btn-primary btn-scri buttons--more" href="{URL*}"><span>{+START,INCLUDE,ICON}NAME=buttons/more{+END} {!VIEW}</span></a>
	</p>
{+END}

{+START,IF_PASSED,TITLE}
	<section class="box box---comcode-page-box"><div class="box-inner">
		{+START,SET,content_box_title}
			{+START,IF,{GIVE_CONTEXT}}
				{!CONTENT_IS_OF_TYPE,{!PAGE},{TITLE*}}
			{+END}

			{+START,IF,{$NOT,{GIVE_CONTEXT}}}
				{TITLE*}
			{+END}
		{+END}
		{+START,IF,{$NOT,{$GET,skip_content_box_title}}}
			<h3>{$GET,content_box_title}</h3>
		{+END}

		{$GET,PREVIEW_CONTENTS}
	</div></section>
{+END}

{+START,IF_NON_PASSED,TITLE}
	{$GET,PREVIEW_CONTENTS}
{+END}
