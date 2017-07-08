<div data-view="ThemeTemplateEditorTab" data-view-params="{+START,PARAMS_JSON,FILE,FILE_ID,HIGHLIGHTER_TYPE,INLUDE_CSS_EDITING,THEME,LIVE_PREVIEW_URL,SCREEN_PREVIEW_URL}{_*}{+END}">

{+START,IF_PASSED_AND_TRUE,OWN_FORM}
<form title="{!PRIMARY_PAGE_FORM}" method="post" action="#" autocomplete="off">
{+END}

{$,Toolbarish}

{+START,IF,{INCLUDE_CSS_EDITING}}
	<div class="template_editing_toolbar" data-view="ToggleableTray">
		<h3 class="js-tray-header">
			<a class="toggleable_tray_button js-btn-tray-toggle" href="#!"><img alt="{!EXPAND}: {$STRIP_TAGS,{!CSS_EDITING_TOOLS}}" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x" /></a>
			<a class="non_link js-btn-tray-toggle" href="#!">{!CSS_EDITING_TOOLS}</a>
		</h3>
		<div class="toggleable_tray js-tray-content" style="display: none" id="c_{FILE_ID*}" aria-expanded="false">
			<div class="css_editor_rhs_column"><section class="box"><div class="box_inner">
				<h3>{!COMMON_CSS_PROPERTIES}:</h3>

				<div class="accordion_trayitem js-tray-accordion-item">
					<h4 class="toggleable_tray_title">
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!"><img alt="{!EXPAND}: Background Properties" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x" /></a>
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!">Background Properties</a>
					</h4>
					<div class="toggleable_tray" style="display: none" aria-expanded="false">
						<table class="columned_table results_table" cellspacing="0" cellpadding="0" border="1" width="100%">
							<tbody><tr>
								<th width="28%" align="left">Property</th>
								<th width="67%" align="left">Description</th>
								<th width="5%" align="left">CSS</th>
							</tr>
							<tr>
								<td><a target="_blank" title="background {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/css3_pr_background.asp">background</a></td>
								<td>Sets all the background properties in one declaration</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="background-color {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_background-color.asp">background-color</a></td>
								<td>Sets the background color of an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="background-image {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_background-image.asp">background-image</a></td>
								<td>Sets the background image for an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="background-position {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_background-position.asp">background-position</a></td>
								<td>Sets the starting position of a background image</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="background-repeat {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_background-repeat.asp">background-repeat</a></td>
								<td>Sets how a background image will be repeated</td>
								<td>1</td>
							</tr>
						</tbody></table>
					</div>
				</div>

				<div class="accordion_trayitem js-tray-accordion-item">
					<h4 class="toggleable_tray_title">
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!"><img alt="{!EXPAND}: Background Properties" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x" /></a>
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!">Border Properties</a>
					</h4>
					<div class="toggleable_tray" style="display: none" aria-expanded="false">
						<table class="columned_table results_table" cellspacing="0" cellpadding="0" border="1" width="100%">
							<tbody><tr>
								<th width="28%" align="left">Property</th>
								<th width="67%" align="left">Description</th>
								<th width="5%" align="left">CSS</th>
							</tr>
							<tr>
								<td><a target="_blank" title="border {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_border.asp">border</a></td>
								<td>Sets all the border properties in one declaration</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="border-bottom {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_border-bottom.asp">border-bottom</a></td>
								<td>Sets all the bottom border properties in one declaration</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="border-color {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_border-color.asp">border-color</a></td>
								<td>Sets the color of the four borders</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="border-left {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_border-left.asp">border-left</a></td>
								<td>Sets all the left border properties in one declaration</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="border-right {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_border-right.asp">border-right</a></td>
								<td>Sets all the right border properties in one declaration</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="border-style {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_border-style.asp">border-style</a></td>
								<td>Sets the style of the four borders</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="border-top {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_border-top.asp">border-top</a></td>
								<td>Sets all the top border properties in one declaration</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="border-width {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_border-width.asp">border-width</a></td>
								<td>Sets the width of the four borders</td>
								<td>1</td>
							</tr>
						</tbody></table>
					</div>
				</div>

				<div class="accordion_trayitem js-tray-accordion-item">
					<h4 class="toggleable_tray_title">
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!"><img alt="{!EXPAND}: Dimension Properties" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x" /></a>
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!">Dimension Properties</a>
					</h4>
					<div class="toggleable_tray" style="display: none" aria-expanded="false">
						<table class="columned_table results_table" cellspacing="0" cellpadding="0" border="1" width="100%">
							<tbody><tr>
								<th width="28%" align="left">Property</th>
								<th width="67%" align="left">Description</th>
								<th width="5%" align="left">CSS</th>
							</tr>
							<tr>
								<td><a target="_blank" title="width {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_dim_width.asp">width</a></td>
								<td>Sets the width of an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="min-width {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_dim_min-width.asp">min-width</a></td>
								<td>Sets the minimum width of an element</td>
								<td>2</td>
							</tr>
							<tr>
								<td><a target="_blank" title="max-width {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_dim_max-width.asp">max-width</a></td>
								<td>Sets the maximum width of an element</td>
								<td>2</td>
							</tr>
							<tr>
								<td><a target="_blank" title="height {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_dim_height.asp">height</a></td>
								<td>Sets the height of an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="min-height {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_dim_min-height.asp">min-height</a></td>
								<td>Sets the minimum height of an element</td>
								<td>2</td>
							</tr>
							<tr>
								<td><a target="_blank" title="max-height {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_dim_max-height.asp">max-height</a></td>
								<td>Sets the maximum height of an element</td>
								<td>2</td>
							</tr>
						</tbody></table>
					</div>
				</div>

				<div class="accordion_trayitem js-tray-accordion-item">
					<h4 class="toggleable_tray_title">
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!"><img alt="{!EXPAND}: List Properties" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x" /></a>
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!">List Properties</a>
					</h4>
					<div class="toggleable_tray" style="display: none" aria-expanded="false">
						<table class="columned_table results_table" cellspacing="0" cellpadding="0" border="1" width="100%">
							<tbody><tr>
								<th width="28%" align="left">Property</th>
								<th width="67%" align="left">Description</th>
								<th width="5%" align="left">CSS</th>
							</tr>
							<tr>
								<td><a target="_blank" title="list-style {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_list-style.asp">list-style</a></td>
								<td>Sets all the properties for a list in one declaration</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="list-style-type {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_list-style-type.asp">list-style-type</a></td>
								<td>Specifies the type of list-item marker</td>
								<td>1</td>
							</tr>
						</tbody></table>
					</div>
				</div>

				<div class="accordion_trayitem js-tray-accordion-item">
					<h4 class="toggleable_tray_title">
						<a class="toggleable_tray_button js-btn-tray-accordion"><img alt="{!EXPAND}: Margin/Padding Properties" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x" /></a>
						<a class="toggleable_tray_button js-btn-tray-accordion">Margin/Padding Properties</a>
					</h4>
					<div class="toggleable_tray" style="display: none" aria-expanded="false">
						<table class="columned_table results_table" cellspacing="0" cellpadding="0" border="1" width="100%">
							<tbody><tr>
								<th width="28%" align="left">Property</th>
								<th width="67%" align="left">Description</th>
								<th width="5%" align="left">CSS</th>
							</tr>
							<tr>
								<td><a target="_blank" title="margin {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_margin.asp">margin</a></td>
								<td>Sets all the margin properties in one declaration</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="margin-bottom {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_margin-bottom.asp">margin-bottom</a></td>
								<td>Sets the bottom margin of an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="margin-left {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_margin-left.asp">margin-left</a></td>
								<td>Sets the left margin of an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="margin-right {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_margin-right.asp">margin-right</a></td>
								<td>Sets the right margin of an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="margin-top {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_margin-top.asp">margin-top</a></td>
								<td>Sets the top margin of an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="padding {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_padding.asp">padding</a></td>
								<td>Sets all the padding properties in one declaration</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="padding-bottom {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_padding-bottom.asp">padding-bottom</a></td>
								<td>Sets the bottom padding of an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="padding-left {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_padding-left.asp">padding-left</a></td>
								<td>Sets the left padding of an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="padding-right {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_padding-right.asp">padding-right</a></td>
								<td>Sets the right padding of an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="padding-top {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_padding-top.asp">padding-top</a></td>
								<td>Sets the top padding of an element</td>
								<td>1</td>
							</tr>
						</tbody></table>
					</div>
				</div>

				<div class="accordion_trayitem js-tray-accordion-item">
					<h4 class="toggleable_tray_title">
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!"><img alt="{!EXPAND}: Positioning Properties" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x" /></a>
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!">Positioning Properties</a>
					</h4>
					<div class="toggleable_tray" style="display: none" aria-expanded="false">
						<table class="columned_table results_table" cellspacing="0" cellpadding="0" border="1" width="100%">
							<tbody><tr>
								<th width="28%" align="left">Property</th>
								<th width="67%" align="left">Description</th>
								<th width="5%" align="left">CSS</th>
							</tr>
							<tr>
								<td><a target="_blank" title="float {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_class_float.asp">float</a></td>
								<td>Specifies whether or not a box should float</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="position {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_class_position.asp">position</a></td>
								<td>Specifies the type of positioning for an element</td>
								<td>2</td>
							</tr>
							<tr>
								<td><a target="_blank" title="left {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_pos_left.asp">left</a></td>
								<td>Sets the left margin edge for a positioned box</td>
								<td>2</td>
							</tr>
							<tr>
								<td><a target="_blank" title="right {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_pos_right.asp">right</a></td>
								<td>Sets the right margin edge for a positioned box</td>
								<td>2</td>
							</tr>
							<tr>
								<td><a target="_blank" title="top {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_pos_top.asp">top</a></td>
								<td>Sets the top margin edge for a positioned box</td>
								<td>2</td>
							</tr>
							<tr>
								<td><a target="_blank" title="bottom {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_pos_bottom.asp">bottom</a></td>
								<td>Sets the bottom margin edge for a positioned box</td>
								<td>2</td>
							</tr>
							<tr>
								<td><a target="_blank" title="clear {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_class_clear.asp">clear</a></td>
								<td>Specifies which sides of an element where other floating elements are not allowed</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="display {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_class_display.asp">display</a></td>
								<td>Specifies the type of box an element should generate</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="overflow {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_pos_overflow.asp">overflow</a></td>
								<td>Specifies what happens if content overflows an element's box</td>
								<td>2</td>
							</tr>
							<tr>
								<td><a target="_blank" title="visibility {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_class_visibility.asp">visibility</a></td>
								<td>Specifies whether or not an element is visible</td>
								<td>2</td>
							</tr>
							<tr>
								<td><a target="_blank" title="z-index {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_pos_z-index.asp">z-index</a></td>
								<td>Sets the stack order of an element</td>
								<td>2</td>
							</tr>
						</tbody></table>
					</div>
				</div>

				<div class="accordion_trayitem js-tray-accordion-item">
					<h4 class="toggleable_tray_title">
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!"><img alt="{!EXPAND}: Text/Font Properties" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x" /></a>
						<a class="toggleable_tray_button js-btn-tray-accordion" href="#!">Text/Font Properties</a>
					</h4>
					<div class="toggleable_tray" style="display: none" aria-expanded="false">
						<table class="columned_table results_table" cellspacing="0" cellpadding="0" border="1" width="100%">
							<tbody><tr>
								<th width="28%" align="left">Property</th>
								<th width="67%" align="left">Description</th>
								<th width="5%" align="left">CSS</th>
							</tr>
							<tr>
								<td><a target="_blank" title="font-family {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_font_font-family.asp">font-family</a></td>
								<td>Specifies the font family for text</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="font-size {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_font_font-size.asp">font-size</a></td>
								<td>Specifies the font size of text</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="font-style {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_font_font-style.asp">font-style</a></td>
								<td>Specifies the font style for text</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="font-weight {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_font_weight.asp">font-weight</a></td>
								<td>Specifies the weight of a font</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="color {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_text_color.asp">color</a></td>
								<td>Sets the color of text</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="line-height {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_dim_line-height.asp">line-height</a></td>
								<td>Sets the line height</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="text-align {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_text_text-align.asp">text-align</a></td>
								<td>Specifies the horizontal alignment of text</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="text-decoration {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_text_text-decoration.asp">text-decoration</a></td>
								<td>Specifies the decoration added to text</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="text-transform {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_text_text-transform.asp">text-transform</a></td>
								<td>Controls the capitalisation of text</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="vertical-align {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_pos_vertical-align.asp">vertical-align</a></td>
								<td>Sets the vertical alignment of an element</td>
								<td>1</td>
							</tr>
							<tr>
								<td><a target="_blank" title="white-space {!LINK_NEW_WINDOW}" href="http://www.w3schools.com/cssref/pr_text_white-space.asp">white-space</a></td>
								<td>Specifies how white-space inside an element is handled</td>
								<td>1</td>
							</tr>
						</tbody></table>
					</div>
				</div>
			</div></section></div>

			<div class="css_editor_lhs_column">
				<div class="css_editor_selectors" id="selectors_{FILE_ID*}" style="display: none"{$,Only becomes visible if it has an anchor page}>
					<div id="selectors_inner_{FILE_ID*}">
						<p class="lonely_label">{!SELECTORS_PARENT_PAGE}:</p>
					</div>

					<section class="box"><div class="box_inner">
						<h2>{!HELP}</h2>
						<p>{!CSS_EDITOR_SELECTOR_TIPS}</p>
					</div></section>
				</div>

				{+START,IF,{$EQ,{FILE},css/global.css}}
					<section class="box"><div class="box_inner">
						<h3>{!QUICK_CSS_CHANGE_LINKS}:</h3>
						<ul>
							<li>
								<a href="#!" class="js-a-editarea-search" data-ea-search="font-family">{!CHANGE_FONT}</a>
							</li>
							<li>
								<a href="#!" class="js-a-editarea-search" data-ea-search="inner_background">{!CHANGE_INNER_BACKGROUND}</a>
							</li>
							<li>
								<a href="#!" class="js-a-editarea-search" data-ea-search="block_background">{!CHANGE_BLOCK_BACKGROUND}</a>
							</li>
							{+START,IF,{$THEME_OPTION,fixed_width}}
								<li>
									<a href="#!" class="js-a-editarea-search" data-ea-search="outer_background">{!CHANGE_OUTER_BACKGROUND}</a>
								</li>
								<li>
									<a href="#!" class="js-a-editarea-search" data-ea-search="&#10;.logo_outer {">{!CHANGE_HEADER_IMAGE}</a>
								</li>
								<li>
									<a href="#!" class="js-a-editarea-search" data-search="#main_website #main_website_inner {">{!CHANGE_FIXED_WIDTH}</a>
								</li>
							{+END}
						</ul>
					</div></section>
				{+END}
			</div>
		</div>
	</div>
{+END}

{+START,IF,{INCLUDE_TEMPCODE_EDITING}}
	<div class="template_editing_toolbar" data-view="ToggleableTray">
		<h3 class="js-tray-header">
			<a class="toggleable_tray_button js-btn-tray-toggle" href="#!"><img alt="{!EXPAND}: {$STRIP_TAGS,{!SYMBOLS_AND_DIRECTIVES}}" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x" /></a>
			<a class="non_link js-btn-tray-toggle" href="#!">{!SYMBOLS_AND_DIRECTIVES}</a>
		</h3>
		<div class="toggleable_tray js-tray-content" style="display: none" id="b_{FILE_ID*}" aria-expanded="false">
			{PARAMETERS}
			{DIRECTIVES}
			{MISC_SYMBOLS}
			{PROGRAMMATIC_SYMBOLS}
			{ABSTRACTION_SYMBOLS}
			{ARITHMETICAL_SYMBOLS}
			{FORMATTING_SYMBOLS}
			{LOGICAL_SYMBOLS}
		</div>
	</div>
{+END}

<div id="e_{FILE_ID*}_wrap" class="main_editor">
	{$,Main editor}

	<label class="accessibility_hidden" for="e_{FILE_ID*}">{!TEMPLATE}</label>
	<div>
		<textarea id="e_{FILE_ID*}" name="e_{FILE_ID*}" cols="70" rows="22" class="wide_field js-ta-tpl-editor textarea_scroll">{CONTENTS*}</textarea>
	</div>
</div>

{$,Buttons}

<div class="float_surrounder buttons_group">
	<input data-disable-on-click="1" class="button_screen buttons__save js-btn-save-content" type="submit" value="{!SAVE}" />

	{+START,SET,preview_buttons}
		{+START,IF_PASSED,LIVE_PREVIEW_URL}
			<!-- Preview in live rendering -->
			<input class="button_screen tabs__preview js-btn-live-preview" type="submit" value="{!LIVE_PREVIEW}" />
		{+END}
		{+START,IF_PASSED,SCREEN_PREVIEW_URL}
			<!-- Preview in screen preview (Lorem ipsum) -->
			<input class="button_screen tabs__preview js-btn-screen-preview" type="submit" value="{!LOREM_PREVIEW}" />
		{+END}
	{+END}
	{+START,IF_NON_EMPTY,{$TRIM,{$GET,preview_buttons}}}
		{$GET,preview_buttons}

		<label for="mobile_preview_{FILE_ID*}"><input type="checkbox" name="mobile_preview_{FILE_ID*}" id="mobile_preview_{FILE_ID*}" value="1" /> {!MOBILE_VERSION}</label>
	{+END}
</div>

{$,GUIDs}

{+START,IF_NON_EMPTY,{GUIDS}}
	<div class="guids" data-view="ToggleableTray">
		<h3 class="js-tray-header">
			<a class="toggleable_tray_button js-btn-tray-toggle" href="#!"><img alt="{!EXPAND}: {$STRIP_TAGS,{!TEMPLATE_GUIDS}}" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x" /></a>
			<a class="non_link js-btn-tray-toggle" href="#!">{!TEMPLATE_GUIDS}</a>
		</h3>
		<div class="toggleable_tray js-tray-content" style="display: none" aria-expanded="false">
			<div class="wide_table_wrap"><table class="columned_table autosized_table revision_box results_table wide_table">
				<thead>
					<tr>
						<th>{!FILENAME}</th>
						<th>{!LINE}</th>
						<th>{!TEMPLATE_GUID}</th>
					</tr>
				</thead>
				<tbody>
					{+START,LOOP,GUIDS}
						<tr class="{$CYCLE,results_table_zebra,zebra_0,zebra_1}">
							<td>
								<kbd>{GUID_FILENAME*}</kbd>
							</td>
							<td>
								{+START,IF,{$ADDON_INSTALLED,code_editor}}
									<a target="_blank" title="{GUID_LINE*} {!LINK_NEW_WINDOW}" href="{$BASE_URL*}/code_editor.php?path={GUID_FILENAME*}&amp;line={GUID_LINE*}">{GUID_LINE*}</a>
								{+END}
								{+START,IF,{$NOT,{$ADDON_INSTALLED,code_editor}}}
									{GUID_LINE*}
								{+END}
							</td>
							<td>
								<a href="#!" class="js-a-insert-guid" data-insert-guid="{GUID_GUID*}">{$?,{GUID_IS_LIVE},<strong>{GUID_GUID*}</strong>,{GUID_GUID*}}</a>
							</td>
						</tr>
					{+END}
				</tbody>
			</table></div>
		</div>
	</div>
{+END}

{$,Related templates}

{+START,IF_NON_EMPTY,{RELATED}}
	<div class="related" data-view="ToggleableTray">
		<h3 class="js-tray-header">
			<a class="toggleable_tray_button js-btn-tray-toggle" href="#!"><img alt="{!EXPAND}: {$STRIP_TAGS,{!RELATED_TEMPLATES}}" title="{!EXPAND}" src="{$IMG*,1x/trays/expand}" srcset="{$IMG*,2x/trays/expand} 2x" /></a>
			<a class="non_link js-btn-tray-toggle" href="#!">{!RELATED_TEMPLATES}</a>
		</h3>
		<div class="toggleable_tray js-tray-content" style="display: none" aria-expanded="false">
			<ul>
				{+START,LOOP,RELATED}
					<li>
						<a href="#!" class="js-a-tpl-editor-add-tab" data-template-file="{_loop_var*}">{_loop_var*}</a>
					</li>
				{+END}
			</ul>
		</div>
	</div>
{+END}

{$,Revisions}

{REVISIONS}

{$,CSS equation helper}

{+START,IF,{INCLUDE_CSS_EDITING}}
	<section class="box box__css_equation_helper"><div class="box_inner">
		<h2>{!CSS_EQUATION_HELPER}</h2>

		<p>{!DESCRIP_CSS_EQUATION_HELPER}</p>

		<p class="vertical_alignment">
			<label for="css_equation">{!CSS_EQUATION_HELPER}</label>
			<input name="css_equation" id="css_equation_{FILE_ID*}" type="text" value="100% seed" />

			<input class="button_screen_item buttons__calculate js-btn-equation-helper" type="submit" value="{!CALCULATE}" />

			&rarr;

			<label class="accessibility_hidden" for="css_result">{!RESULT}</label>
			<output><input readonly="readonly" name="css_result" id="css_result_{FILE_ID*}" type="text" value="({!RESULT})" /></output>
		</p>
	</div></section>
{+END}

{+START,IF_PASSED_AND_TRUE,OWN_FORM}
</form>
{+END}

</div>