{TITLE}

<p>
	{!LEADER_BOARD_PAGE_TEXT}
</p>

{WEEKS}

{+START,IF_PASSED,PAGINATION}
	{+START,IF_NON_EMPTY,{PAGINATION}}
		<div class="pagination-spacing clearfix">
			{PAGINATION}
		</div>
	{+END}
{+END}
