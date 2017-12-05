{$,Forum/private topic search}
{+START,IF,{$EQ,{$PAGE},forumview}}
	{+START,IF,{$EQ,{$_GET,type},pt}}
		<div class="cns_search_box">
			<form title="{!SEARCH}" action="{$URL_FOR_GET_FORM*,{$PAGE_LINK*,_SEARCH:search:results:cns_own_pt,1}}" method="get" autocomplete="off">
				{$HIDDENS_FOR_GET_FORM,{$PAGE_LINK,_SEARCH:search:results:cns_own_pt,1}}

				<div class="vertical_alignment">
					<label class="accessibility_hidden" for="member_bar_search">{!_SEARCH_PRIVATE_TOPICS}</label><input maxlength="255" type="text" name="content" id="member_bar_search" placeholder="{!_SEARCH_PRIVATE_TOPICS}" /><input class="button_micro buttons--search" type="submit" data-disable-on-click="1" value="{!SEARCH}" /> <a class="associated-link" href="{$PAGE_LINK*,_SEARCH:search:browse:cns_own_pt}" title="{!MORE}: {!search:SEARCH_OPTIONS}">{!MORE}</a>
				</div>
			</form>
		</div>
	{+END}
	{+START,IF,{$NEQ,{$_GET,type},pt}}
		<div class="cns_search_box">
			<form title="{!SEARCH}" action="{$URL_FOR_GET_FORM*,{$PAGE_LINK*,_SEARCH:search:results:cns_posts:search_under={$_GET,id},1}}" method="get" autocomplete="off">
				{$HIDDENS_FOR_GET_FORM,{$PAGE_LINK,_SEARCH:search:results:cns_posts:search_under={$_GET,id},1}}

				<div class="vertical_alignment">
					<label class="accessibility_hidden" for="member_bar_search">{!SEARCH_FORUM_POSTS}</label><input maxlength="255" type="text" name="content" id="member_bar_search" placeholder="{!SEARCH_FORUM_POSTS}" /><input class="button_micro buttons--search" type="submit" data-disable-on-click="1" value="{!SEARCH}" /> <a class="associated-link" href="{$PAGE_LINK*,_SEARCH:search:browse:cns_posts:search_under={$_GET,id}}" title="{!MORE}: {!search:SEARCH_OPTIONS}">{!MORE}</a>
				</div>
			</form>
		</div>
	{+END}
{+END}

{$,Topic search}
{+START,IF,{$EQ,{$PAGE},topicview}}
	<div class="cns_search_box">
		<form title="{!SEARCH}" action="{$URL_FOR_GET_FORM*,{$PAGE_LINK,_SEARCH:search:results:cns_within_topic:search_under={$_GET,id}}}" method="get" autocomplete="off">
			{$HIDDENS_FOR_GET_FORM,{$PAGE_LINK,_SEARCH:search:results:cns_within_topic:search_under={$_GET,id}}}

			<div class="vertical_alignment">
				<label class="accessibility_hidden" for="member_bar_search">{!SEARCH_POSTS_WITHIN_TOPIC}</label><input maxlength="255" type="text" name="content" id="member_bar_search" placeholder="{!SEARCH_POSTS_WITHIN_TOPIC}" /><input class="button_micro buttons--search" type="submit" data-disable-on-click="1" value="{!SEARCH}" /> <a class="associated-link" href="{$PAGE_LINK*,_SEARCH:search:browse:cns_within_topic:search_under={$_GET,id}}" title="{!MORE}: {!search:SEARCH_OPTIONS}">{!MORE}</a>
			</div>
		</form>
	</div>
{+END}

{$,General search}
{+START,IF,{$NEQ,{$PAGE},forumview,topicview}}
	<div class="cns_search_box">
		<form title="{!SEARCH}" action="{$URL_FOR_GET_FORM*,{$PAGE_LINK,_SEARCH:search:results}}" method="get" autocomplete="off">
			{$HIDDENS_FOR_GET_FORM,{$PAGE_LINK,_SEARCH:search:results}}

			<div class="vertical_alignment">
				<label class="accessibility_hidden" for="member_bar_search">{!SEARCH}</label><input maxlength="255" type="text" name="content" id="member_bar_search" placeholder="{!SEARCH}" /><input class="button_micro buttons--search" type="submit" data-disable-on-click="1" value="{!SEARCH}" /> <a class="associated-link" href="{$PAGE_LINK*,_SEARCH:search:browse}" title="{!MORE}: {!search:SEARCH_OPTIONS}">{!MORE}</a>
			</div>
		</form>
	</div>
{+END}
