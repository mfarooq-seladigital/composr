{$REQUIRE_JAVASCRIPT,core_permission_management}

<tr class="{$CYCLE,zebra,zebra-0,zebra-1}" data-tpl="permissionRow">
	<th>
		{PERMISSION*}
		{+START,IF_PASSED,DESCRIPTION}
			{+START,IF,{$DESKTOP}}
				<span class="inline-desktop">
					<a class="button-icon" data-mouseover-activate-tooltip="['{DESCRIPTION;^=}','auto']">
						{+START,INCLUDE,ICON}
							NAME=help
							ICON_SIZE=24
						{+END}
					</a>
				</span>
			{+END}

			<span class="block-mobile">
				{DESCRIPTION}
			</span>
		{+END}
	</th>
	{CELLS}
	<td>
		<button title="{!SET_ALL_PERMISSIONS_ON_ROW}" class="button-micro js-click-input-toggle-value" type="button" data-click-eval="{CODE*}">{$?,{HAS},-,+}</button>
	</td>
</tr>
