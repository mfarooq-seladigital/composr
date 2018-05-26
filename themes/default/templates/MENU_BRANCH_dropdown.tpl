{+START,SET,branch}
{$,The line below is just a fudge - we need CHILDREN to evaluate first, so it doesn't interfere with our 'RAND' variable}
{$SET,HAS_CHILDREN,{$IS_NON_EMPTY,{CHILDREN}}}

{$SET,RAND,{$RAND}}
{$SET,img,{$?,{$AND,{$IS_EMPTY,{IMG}},{$LT,{THE_LEVEL},3}},{$IMG,icons/content_types/page},{IMG}}}
{$SET,img_html,<img class="icon" alt="" src="{$GET*,img}" />}

{+START,IF,{$PREG_MATCH,^{$BASE_URL}/themes/[^/]+/images(_custom)?/icons/,{$GET,img}}}
{$SET,img_name,{$PREG_REPLACE,\.(png|gif|svg)$,,{$PREG_REPLACE,^{$BASE_URL}/themes/[^/]+/images(_custom)?/icons/,,{$GET,img}}}}
{$SET,img_html,{+START,INCLUDE,ICON}NAME={$GET,img_name}{+END}}
{+END}

{+START,IF,{TOP_LEVEL}}
<li class="dropdown-menu-item toplevel {$?,{CURRENT},current,non-current}{$?,{$GET,HAS_CHILDREN}, has-children}{$?,{FIRST}, first}{$?,{LAST}, last}">
	<a {+START,INCLUDE,MENU_LINK_PROPERTIES}{+END} class="dropdown-menu-item-a toplevel-link">
		{+START,IF_NON_EMPTY,{$GET,img}}<span class="dropdown-menu-item-icon">{$GET,img_html}</span>{+END}
		<span class="dropdown-menu-item-caption">{CAPTION}</span>
	</a>
	{+START,IF,{$GET,HAS_CHILDREN}}{+START,IF,{$DESKTOP}}
	<ul aria-haspopup="true" class="dropdown-menu-items nlevel" style="display: none">
		{CHILDREN}
	</ul>
	{+END}{+END}
</li>
{+END}

{+START,IF,{$NOT,{TOP_LEVEL}}}
<li class="dropdown-menu-item nlevel {$?,{CURRENT},current,non-current}{$?,{$GET,HAS_CHILDREN}, has-children}{$?,{FIRST}, first}{$?,{LAST}, last} {+START,IF_PASSED,SIBLINGS}siblings-{SIBLINGS*}{+END}">
	<a {+START,IF_NON_EMPTY,{URL}}{+START,INCLUDE,MENU_LINK_PROPERTIES}{+END}{+END} {+START,IF_EMPTY,{URL}}href="#!"{+END} class="dropdown-menu-item-a nlevel-link">
		{+START,IF_NON_EMPTY,{$GET,img}}<span class="dropdown-menu-item-icon">{$GET,img_html}</span>{+END}
		<span class="dropdown-menu-item-caption">{CAPTION}</span>
	</a>

	{+START,IF,{$GET,HAS_CHILDREN}}
	<ul aria-haspopup="true" class="dropdown-menu-items nlevel" style="display: none">
		{+START,IF_NON_EMPTY,{URL}}{$,Add duplicate parent link in child items that can be opened when using the hamburger menu (parent itself will toggle the child items)}
		<li class="block-mobile dropdown-menu-item nlevel {$?,{CURRENT},current,non-current} has-img">
			<a {+START,INCLUDE,MENU_LINK_PROPERTIES}{+END} class="dropdown-menu-item-a nlevel-link">{+START,IF_NON_EMPTY,{$GET,img}}<span class="dropdown-menu-item-icon">{$GET,img_html}</span>{+END}<span class="dropdown-menu-item-caption">{CAPTION}</span></a>
		</li>
		{+END}
		{CHILDREN}
	</ul>
	{+END}
</li>
{+END}
{+END}{$REPLACE,	,,{$REPLACE,
+,,{$GET,branch}}}