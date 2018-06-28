<div data-tpl="pointsGive">
	{+START,IF,{$NOT,{$HAS_ACTUAL_PAGE_ACCESS,admin_points}}}
		{$,Regular member}
		<p class="points-give-box-header">
			<span>{!GIVE_TO,{$USERNAME*,{MEMBER},1}}</span>
			{+START,IF_NON_EMPTY,{VIEWER_GIFT_POINTS_AVAILABLE}}
				{!GIVE_TEXT,{VIEWER_GIFT_POINTS_AVAILABLE*}}
			{+END}
			{+START,IF_EMPTY,{VIEWER_GIFT_POINTS_AVAILABLE}}
				{!GIVE_TEXT_UNLIMITED}
			{+END}
		</p>

		<form title="{!GIVE_POINTS}" method="post" class="js-submit-check-form" action="{GIVE_URL*}#tab--points" autocomplete="off">
			{$INSERT_SPAMMER_BLACKHOLE}

			<div>
				<label class="accessibility-hidden" for="give-amount">{!AMOUNT}</label>
				<input maxlength="8" data-prevent-input="[^\-\d{$BACKSLASH}{$DECIMAL_POINT*}]" size="7" id="give-amount" class="form-control form-control-inline input-integer-required" placeholder="{!AMOUNT}" type="text" name="amount" />

				<label class="accessibility-hidden" for="give-reason">{!REASON}</label>
				<input maxlength="255" size="26" id="give-reason" class="form-control form-control-inline input-line-required" placeholder="{!REASON}" type="text" name="reason" />

				<label class="points-anon" for="give-anonymous">{!TICK_ANON}: <input type="checkbox" id="give-anonymous" name="anonymous" value="1" /></label>

				<button id="give-points-submit" class="btn btn-primary btn-scri buttons--points" type="submit">{!GIVE_POINTS}</button>
			</div>
		</form>
	{+END}

	{+START,IF,{$HAS_ACTUAL_PAGE_ACCESS,admin_points}}
		{$,Admin}
		<p class="points-give-box-header">
			<span>{!MODIFY_POINTS}</span>
			{+START,IF_NON_EMPTY,{VIEWER_GIFT_POINTS_AVAILABLE}}
				{!GIVE_TEXT,{VIEWER_GIFT_POINTS_AVAILABLE*}}
			{+END}
			{+START,IF_EMPTY,{VIEWER_GIFT_POINTS_AVAILABLE}}
				{!GIVE_TEXT_UNLIMITED}
			{+END}
		</p>

		<form title="{!GIVE_POINTS}" method="post" class="js-submit-check-form" action="{GIVE_URL*}#tab--points" autocomplete="off">
			{$INSERT_SPAMMER_BLACKHOLE}

			<div>
				<div class="points-give-shared-options">
					<label class="accessibility-hidden" for="give-amount">{!AMOUNT}</label>
					<input maxlength="10" data-prevent-input="[^\-\d{$BACKSLASH}{$DECIMAL_POINT*}]" size="7" id="give-amount" class="form-control form-control-inline input-integer-required" placeholder="{!AMOUNT}" type="text" name="amount" />

					<label class="accessibility-hidden" for="give-reason">{!REASON}</label>
					<input maxlength="255" size="18" id="give-reason" class="form-control form-control-inline input-line-required" placeholder="{!REASON}" type="text" name="reason" />

					<button id="give-points-submit" class="btn btn-primary btn-scri buttons--points" type="submit">{!PROCEED_SHORT}</button>
				</div>

				<div class="points-give-choices">
					<p class="points-give-choice-line first">
						<label for="trans-type-gift"><strong>{!GIVE_POINTS}</strong> <input checked="checked" type="radio" id="trans-type-gift" name="trans_type" value="gift" /></label> <span class="arr">&rarr;</span>
						<label class="sub-option points-anon" for="give-anonymous">{!TICK_ANON}: <input type="checkbox" id="give-anonymous" name="anonymous" value="1" /></label>
					</p>

					<p class="points-give-choice-line">
						<strong>{!MODIFY_POINTS}</strong> <span class="arr">&rarr;</span>
						<label class="sub-option" for="trans-type-charge">{!CHARGE} <input type="radio" id="trans-type-charge" name="trans_type" value="charge" /></label>
						<label class="sub-option" for="trans-type-refund">{!REFUND} <input type="radio" id="trans-type-refund" name="trans_type" value="refund" /></label>
					</p>
				</div>
			</div>
		</form>
	{+END}
</div>
