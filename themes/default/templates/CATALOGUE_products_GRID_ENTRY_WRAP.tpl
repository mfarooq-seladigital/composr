<section class="box box---catalogue-products-grid-entry-wrap"><div class="box-inner">
	<h3><span class="name">{FIELD_0}</span></h3>

	{+START,IF_NON_EMPTY,{FIELD_7_THUMB}}
		<div class="catalogue-entry-box-thumbnail">
			<a href="{VIEW_URL*}">{FIELD_7_THUMB}</a>
		</div>
	{+END}

	{+START,IF,{ALLOW_RATING}}{+START,IF_NON_EMPTY,{$TRIM,{RATING}}}
		<div class="ratings">
			{RATING}
		</div>
	{+END}{+END}

	<div class="price-box">
		<span class="price">{$CURRENCY,{FIELD_2_PLAIN},,{$?,{$CONFIG_OPTION,currency_auto},{$CURRENCY_USER},{$CURRENCY}}}</span>
	</div>

	<div class="buttons-group">
		{+START,IF_PASSED,ADD_TO_CART}
			<a class="button-screen-item buttons--cart-add" href="{ADD_TO_CART*}" title="{!ADD_TO_CART}"><span>{+START,INCLUDE,ICON}NAME=buttons/cart_add{+END} {!BUY}</span></a>
		{+END}
		<a class="button-screen-item buttons--more" href="{VIEW_URL*}" title="{!VIEW_PRODUCT}"><span>{!VIEW}</span> {+START,INCLUDE,ICON}NAME=buttons/more{+END}</a>
	</div>
</div></section>
