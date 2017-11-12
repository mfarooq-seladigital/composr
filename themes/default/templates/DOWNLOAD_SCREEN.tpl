<div itemscope="itemscope" itemtype="http://schema.org/ItemPage">
	{TITLE}

	{+START,IF_NON_EMPTY,{OUTMODE_URL}}
		<p class="red_alert">
			<a href="{OUTMODE_URL*}">{!OUTMODED}</a>
		</p>
	{+END}

	{WARNING_DETAILS}

	<div class="float_surrounder">
		<div class="download_metadata">
			<div class="download_now_wrapper">
				<div class="box box___download_screen" data-toggleable-tray="{}">
					{+START,IF_PASSED,LICENCE_HYPERLINK}
					<p class="download_licence">
						{!D_BEFORE_PROCEED_AGREE,{LICENCE_HYPERLINK}}
					</p>

					<div class="toggleable_tray_title js-tray-header">
						<a class="toggleable_tray_button js-tray-onclick-toggle-tray" href="#!"><img alt="{!EXPAND}: {!I_AGREE}" title="{!EXPAND}" src="{$IMG*,1x/trays/expand2}" srcset="{$IMG*,2x/trays/expand2} 2x" /></a>
						<a class="toggleable_tray_button js-tray-onclick-toggle-tray" href="#!">{!I_AGREE}</a>
					</div>

					<div class="toggleable_tray js-tray-content" style="display: none" aria-expanded="false">
					{+END}
					{+START,IF_NON_PASSED,LICENCE_HYPERLINK}
					<div class="box_inner">
					{+END}
						<div class="download_now" itemprop="significantLinks">
							{+START,IF,{MAY_DOWNLOAD}}
								<p class="download_link associated_link suggested_link"><a {+START,IF,{$NOT,{$INLINE_STATS}}} data-click-ga-track="{ category: '{!DOWNLOAD;^*}', action: '{ORIGINAL_FILENAME;^*}' }"{+END} rel="nofollow" href="{DOWNLOAD_URL*}"><strong>{!DOWNLOAD_NOW}</strong></a></p>
							{+END}
							{+START,IF,{$NOT,{MAY_DOWNLOAD}}}
								<p>{!NO_DOWNLOAD_ACCESS}</p>
							{+END}
							<p class="download_filesize">({FILE_SIZE*})</p>
						</div>
					</div>
				</div>
			</div>

			<div class="download_stats_wrapper">
				<div class="wide_table_wrap"><table class="map_table download_stats results_table wide_table" role="note">
					{+START,IF,{$DESKTOP}}
						<colgroup>
							<col class="download_field_name_column" />
							<col class="download_field_value_column" />
						</colgroup>
					{+END}

					<tbody>
						<tr>
							<th class="de_th metadata_title">{!ADDED}</th>
							<td>
								<time datetime="{$FROM_TIMESTAMP*,Y-m-d\TH:i:s\Z,{DATE_RAW}}" itemprop="datePublished">{DATE*}</time>
							</td>
						</tr>

						<tr>
							{+START,IF_NON_EMPTY,{AUTHOR_URL}}
								<th class="de_th metadata_title">{!BY}</th>
								<td>
									<a rel="author" href="{AUTHOR_URL*}" title="{!AUTHOR}: {AUTHOR*}">{AUTHOR*}</a>
									{+START,INCLUDE,MEMBER_TOOLTIP}SUBMITTER={$AUTHOR_MEMBER,{AUTHOR}}{+END}
								</td>
							{+END}

							{+START,IF_EMPTY,{AUTHOR_URL}}{+START,IF_NON_EMPTY,{$USERNAME,{SUBMITTER},1}}
								<th class="de_th metadata_title">{!BY}</th>
								<td>
									<a rel="author" href="{$MEMBER_PROFILE_URL*,{SUBMITTER}}">{$USERNAME*,{SUBMITTER},1}</a>
									{+START,INCLUDE,MEMBER_TOOLTIP}{+END}
								</td>
							{+END}{+END}
						</tr>

						{+START,IF_NON_EMPTY,{EDIT_DATE}}
							<tr>
								<th class="de_th metadata_title">{!EDITED}</th>
								<td>
									<time datetime="{$FROM_TIMESTAMP*,Y-m-d\TH:i:s\Z,{EDIT_DATE_RAW}}">{EDIT_DATE*}</time>
								</td>
							</tr>
						{+END}

						{+START,IF,{$INLINE_STATS}}
							<tr>
								<th class="de_th metadata_title">{!COUNT_VIEWS}</th>
								<td>{VIEWS*}</td>
							</tr>
						{+END}

						<tr>
							<th class="de_th metadata_title">{!COUNT_DOWNLOADS}</th>
							<td>
								<meta itemprop="interactionCount" content="UserDownloads:{$PREG_REPLACE*,[^\d],,{NUM_DOWNLOADS}}"/>
								{NUM_DOWNLOADS*}
							</td>
						</tr>

						{$SET,review_status,{$REVIEW_STATUS,download,{ID}}}
						{+START,IF_NON_EMPTY,{$GET,review_status}}
							<tr>
								<td colspan="2">
									{$GET,review_status}
								</td>
							</tr>
						{+END}
					</tbody>
				</table></div>
			</div>

			{+START,IF_NON_EMPTY,{RATING_DETAILS}}
				<div class="ratings right">
					{RATING_DETAILS}
				</div>
			{+END}

			{+START,IF_NON_EMPTY,{TRACKBACK_DETAILS}}
				<div class="trackbacks right">
					{TRACKBACK_DETAILS}
				</div>
			{+END}
		</div>

		<div class="download_description" itemprop="description">
			{+START,IF_NON_EMPTY,{DESCRIPTION}}
				{$PARAGRAPH,{DESCRIPTION}}
			{+END}

			{+START,IF_NON_EMPTY,{ADDITIONAL_DETAILS}}
				<h2>{!ADDITIONAL_INFO}</h2>

				{ADDITIONAL_DETAILS}
			{+END}

			{$SET,bound_catalogue_entry,{$CATALOGUE_ENTRY_FOR,download,{ID}}}
			{+START,IF_NON_EMPTY,{$GET,bound_catalogue_entry}}{$CATALOGUE_ENTRY_ALL_FIELD_VALUES,{$GET,bound_catalogue_entry}}{+END}
		</div>
	</div>

	{+START,IF_NON_EMPTY,{IMAGES_DETAILS}}
		<div class="box box___download_screen"><div class="box_inner">
			<h2>{!IMAGES}</h2>

			{$REQUIRE_JAVASCRIPT,core_rich_media}
			{$REQUIRE_CSS,carousels}

			{$SET,carousel_id,{$RAND}}

			<div id="carousel_{$GET*,carousel_id}" class="carousel" style="display: none" data-view="Carousel" data-view-params="{+START,PARAMS_JSON,carousel_id}{_*}{+END}">
				<div class="move_left js-btn-car-move" data-move-amount="-100"></div>
				<div class="move_right js-btn-car-move" data-move-amount="+100"></div>

				<div class="main">
				</div>
			</div>

			<div class="carousel_temp" id="carousel_ns_{$GET*,carousel_id}">
				{IMAGES_DETAILS}
			</div>

			{$,<p class="download_start_slideshow"><span class="associated_link"><a target="_blank" title="\{!galleries:_SLIDESHOW\}: \{!LINK_NEW_WINDOW\}" href="\{$PAGE_LINK*,_SEARCH:galleries:image:\{$GET*,FIRST_IMAGE_ID\}:slideshow=1:wide_high=1\}">\{!galleries:_SLIDESHOW\}</a></span></p>}
		</div></div>
	{+END}

	{+START,IF,{$THEME_OPTION,show_screen_actions}}{$BLOCK,failsafe=1,block=main_screen_actions,title={NAME}}{+END}

	{+START,IF,{$THEME_OPTION,show_content_tagging}}{TAGS}{+END}

	{$,Load up the staff actions template to display staff actions uniformly (we relay our parameters to it)...}
	{+START,INCLUDE,STAFF_ACTIONS}
		1_URL={EDIT_URL*}
		1_TITLE={!EDIT}
		1_ACCESSKEY=q
		1_REL=edit
		1_ICON=menu/_generic_admin/edit_this
		{+START,IF,{$ADDON_INSTALLED,galleries}}
			2_URL={ADD_IMG_URL*}
			2_TITLE={!ADD_IMAGE}
			2_ICON=menu/_generic_admin/add_one
		{+END}
		{+START,IF,{$ADDON_INSTALLED,tickets}}
			3_URL={$PAGE_LINK*,_SEARCH:report_content:content_type=download:content_id={ID}:redirect={$SELF_URL&}}
			3_TITLE={!report_content:REPORT_THIS}
			3_ICON=buttons/report
			3_REL=report
		{+END}
	{+END}

	<div class="content_screen_comments">
		{COMMENT_DETAILS}
	</div>
</div>
