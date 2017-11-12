{$REQUIRE_JAVASCRIPT,facebook_support}

<section id="tray_{!MEMBER|}" data-require-javascript="facebook_support" data-tpl="cnsGuestBar" data-toggleable-tray="{ save: true }" class="box cns_information_bar_outer">
	<h2 class="toggleable_tray_title js-tray-header">
		<a class="toggleable_tray_button js-tray-onclick-toggle-tray inline_desktop" href="#!"><img alt="{!CONTRACT}: {$STRIP_TAGS,{!_LOGIN}}" title="{!CONTRACT}" src="{$IMG*,1x/trays/contract2}" srcset="{$IMG*,2x/trays/contract2} 2x" /></a>

		<a class="toggleable_tray_button js-tray-onclick-toggle-tray" href="#!">{!_LOGIN}{+START,IF,{$HAS_ACTUAL_PAGE_ACCESS,search}} / {!SEARCH}{+END}</a>
	</h2>

	<div class="toggleable_tray js-tray-content">
		<div class="cns_information_bar float_surrounder">
			<div class="cns_guest_column cns_guest_column_a">
				<form title="{!_LOGIN}" class="inline js-submit-check-username-for-blankness" action="{LOGIN_URL*}" method="post" autocomplete="on">
					{$INSERT_SPAMMER_BLACKHOLE}

					<div>
						<div class="accessibility_hidden"><label for="member_bar_login_username">{$LOGIN_LABEL}</label></div>
						<div class="accessibility_hidden"><label for="member_bar_s_password">{!PASSWORD}</label></div>
						<input size="15" type="text" placeholder="{!USERNAME}" id="member_bar_login_username" name="login_username" />
						<input size="15" type="password" placeholder="{!PASSWORD}" name="password" id="member_bar_s_password" />
						{+START,IF,{$CONFIG_OPTION,password_cookies}}
							<label for="remember">{!REMEMBER_ME}:</label>
							<input class="{+START,IF,{$NOT,{$CONFIG_OPTION,remember_me_by_default}}}js-click-confirm-remember-me{+END}"{+START,IF,{$CONFIG_OPTION,remember_me_by_default}} checked="checked"{+END} type="checkbox" value="1" id="remember" name="remember" />
						{+END}
						<input class="button_screen_item menu__site_meta__user_actions__login" type="submit" value="{!_LOGIN}" />

						{+START,IF_EMPTY,{$FB_CONNECT_UID}}
							{+START,IF_NON_EMPTY,{$CONFIG_OPTION,facebook_appid}}{+START,IF,{$CONFIG_OPTION,facebook_allow_signups}}
								<div class="fb-login-button" data-scope="email{$,Asking for this stuff is now a big hassle as it needs a screencast(s) making: user_birthday,user_about_me,user_hometown,user_location,user_website}{+START,IF,{$CONFIG_OPTION,facebook_auto_syndicate}},publish_actions,publish_pages{+END}"></div>
							{+END}{+END}
						{+END}
						<ul class="horizontal_links associated_links_block_group">
							<li><a href="{JOIN_URL*}">{!_JOIN}</a></li>
							<li><a data-open-as-overlay="{}" rel="nofollow" href="{FULL_LOGIN_URL*}" title="{!MORE}: {!_LOGIN}">{!MORE}</a></li>
						</ul>
					</div>
				</form>
			</div>
			{+START,IF,{$ADDON_INSTALLED,search}}{+START,IF,{$HAS_ACTUAL_PAGE_ACCESS,search}}
				<div class="cns_guest_column cns_guest_column_c">
					{+START,INCLUDE,MEMBER_BAR_SEARCH}{+END}
				</div>
			{+END}{+END}

			<nav class="cns_guest_column cns_member_column_d">
				{$,<p class="cns_member_column_title">{!VIEW}:</p>}
				<ul class="actions_list">
					<li><a data-open-as-overlay="{}" href="{NEW_POSTS_URL*}">{!POSTS_SINCE}</a></li>
					<li><a data-open-as-overlay="{}" href="{UNANSWERED_TOPICS_URL*}">{!UNANSWERED_TOPICS}</a></li>
				</ul>
			</nav>
		</div>
	</div>
</section>
