<section id="tray_{!MEMBER|}" data-toggleable-tray="{ save: true }" class="box cns-information-bar-outer">
	<h2 class="toggleable-tray-title js-tray-header">
		<a class="toggleable-tray-button js-tray-onclick-toggle-tray inline_desktop" href="#!"><img alt="{!CONTRACT}: {$STRIP_TAGS,{!MEMBER}}" title="{!CONTRACT}" src="{$IMG*,1x/trays/contract2}" /></a>
		<a class="toggleable-tray-button js-tray-onclick-toggle-tray" href="#!">{!MEMBER_INFORMATION,{$USERNAME*,{$MEMBER},1}}{+START,IF,{$HAS_ACTUAL_PAGE_ACCESS,search}} / {!SEARCH}{+END}</a>
	</h2>

	<div class="toggleable-tray js-tray-content">
		<div class="cns-information-bar float-surrounder">
			{+START,IF_NON_EMPTY,{AVATAR_URL}}
				<div style="min-height: {$MAX,100,{MAX_AVATAR_HEIGHT|}}px" class="cns_member_column cns_member_column_a">
					<img alt="{!AVATAR}" title="{!AVATAR}" src="{$ENSURE_PROTOCOL_SUITABILITY*,{AVATAR_URL}}" />
				</div>
			{+END}

			<div style="min-height: {$MAX,100,{MAX_AVATAR_HEIGHT|}}px" class="cns_member_column cns_member_column_b">
				<p class="cns_member_column_title">{!WELCOME_BACK,<a href="{PROFILE_URL*}">{$DISPLAYED_USERNAME*,{USERNAME}}</a>}</p>
				{+START,IF,{$NOT,{$IS_HTTPAUTH_LOGIN}}}
					<div class="inline_desktop">
						<form class="inline associated-link" title="{!LOGOUT}" method="post" action="{LOGOUT_URL*}" autocomplete="off"><input class="button_hyperlink" type="submit" value="{!LOGOUT}" /></form>
					</div>
				{+END}

				<dl class="meta_details_list">
					{+START,IF,{$ADDON_INSTALLED,points}}
						<dt class="field-name"><abbr title="{!LIFETIME_POINTS,{$NUMBER_FORMAT*,{$AVAILABLE_POINTS}}}">{!POINTS}</abbr>:</dt> <dd><a {+START,IF_PASSED,NUM_POINTS_ADVANCE} title="{!GROUP_ADVANCE,{NUM_POINTS_ADVANCE*}}"{+END} href="{$PAGE_LINK*,site:points:member:{$MEMBER}}">{NUM_POINTS*}</a></dd>
					{+END}
					<dt class="field-name">{!COUNT_POSTS}:</dt> <dd>{NUM_POSTS*}</dd>
					<dt class="field-name">{$?,{$MOBILE},{!USERGROUP},{!PRIMARY_GROUP}}:</dt> <dd>{PRIMARY_GROUP*}</dd>
				</dl>
			</div>

			<div style="min-height: {$MAX,100,{MAX_AVATAR_HEIGHT|}}px" class="cns_member_column cns_member_column_c">
				{+START,IF,{$ADDON_INSTALLED,search}}{+START,IF,{$HAS_ACTUAL_PAGE_ACCESS,search}}
					<div class="box nested"><div class="box-inner">{+START,INCLUDE,MEMBER_BAR_SEARCH}{+END}</div></div>
				{+END}{+END}

				<div class="cns_member_column_last_visit">{!LAST_VISIT,{LAST_VISIT_DATE*}}
					<ul class="meta_details_list">
						<li>{!NEW_TOPICS,{NEW_TOPICS*}}</li>
						<li>{!NEW_POSTS,{NEW_POSTS*}}</li>
					</ul>
				</div>
			</div>

			<nav style="min-height: {$MAX,100,{MAX_AVATAR_HEIGHT|}}px" class="cns_member_column cns_member_column_d">
				{$,<p class="cns_member_column_title">{!VIEW}:</p>}
				<ul class="actions-list">
					<!--<li><a href="{PRIVATE_TOPIC_URL*}">{!PRIVATE_TOPICS}{+START,IF_NON_EMPTY,{PT_EXTRA}} <span class="cns_member_column_pts">{PT_EXTRA}</span>{+END}</a></li>-->
					<li><a {+START,IF,{$DESKTOP}} data-open-as-overlay="{}"{+END} href="{NEW_POSTS_URL*}">{!POSTS_SINCE}</a></li>
					<li><a {+START,IF,{$DESKTOP}} data-open-as-overlay="{}"{+END} href="{UNREAD_TOPICS_URL*}">{!TOPICS_UNREAD}</a></li>
					<li><a {+START,IF,{$DESKTOP}} data-open-as-overlay="{}"{+END} href="{RECENTLY_READ_URL*}">{!RECENTLY_READ}</a></li>
					<li><a {+START,IF,{$DESKTOP}} data-open-as-overlay="{}"{+END} href="{INLINE_PERSONAL_POSTS_URL*}">{!INLINE_PERSONAL_POSTS}</a></li>
					<li><a {+START,IF,{$DESKTOP}} data-open-as-overlay="{}"{+END} href="{UNANSWERED_TOPICS_URL*}">{!UNANSWERED_TOPICS}</a></li>
					<li><a {+START,IF,{$DESKTOP}} data-open-as-overlay="{}"{+END} href="{INVOLVED_TOPICS_URL*}">{!INVOLVED_TOPICS}</a></li>
				</ul>
			</nav>
		</div>
	</div>
</section>
