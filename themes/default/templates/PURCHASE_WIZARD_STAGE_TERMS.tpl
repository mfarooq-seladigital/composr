{$REQUIRE_JAVASCRIPT,ecommerce}
<form data-tpl="purchaseWizardStageTerms" title="{!PRIMARY_PAGE_FORM}"{+START,IF_NON_PASSED_OR_FALSE,GET} method="post" action="{URL*}"{+END}{+START,IF_PASSED_AND_TRUE,GET} method="get" action="{$URL_FOR_GET_FORM*,{URL}}"{+END} autocomplete="off">
	{+START,IF_NON_PASSED_OR_FALSE,GET}{$INSERT_SPAMMER_BLACKHOLE}{+END}

	{+START,IF_PASSED_AND_TRUE,GET}{$HIDDENS_FOR_GET_FORM,{URL}}{+END}

	<p>{!AGREEMENT_PROCESS}</p>

	<p class="lonely_label">{!AGREEMENT}:</p>

	<div class="purchase_terms">{TERMS*}</div>

	<p>
		<input type="checkbox" id="confirm" name="confirm" value="1" class="js-checkbox-click-toggle-proceed-btn" /><label for="confirm">{!I_AGREE}</label>
	</p>

	<p>
		<button type="button" data-disable-on-click="1" class="button_screen buttons__no js-click-btn-i-disagree" data-tp-location="{$PAGE_LINK*,:}">{!I_DISAGREE}</button>

		<input accesskey="u" data-disable-on-click="1" class="button_screen buttons__yes" type="submit" value="{!PROCEED}" disabled="disabled" id="proceed_button" />
	</p>
</form>