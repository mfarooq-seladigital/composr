<div class="invoice_box">
	<table>
		<tbody>
			<tr class="top">
				<td class="title">
					<img title="{$SITE_NAME*}" alt="{$SITE_NAME*}" src="{$IMG*,logo/standalone_logo}" /><br />
					{+START,IF_NON_EMPTY,{$CONFIG_OPTION,tax_number}}
						<br />{$TAX_NUMBER_LABEL} {$CONFIG_OPTION,tax_number}
					{+END}
				</td>

				<td>
					{!INVOICE} #: {TXN_ID*}<br />
					{STATUS*}: {DATE*}
				</td>
			</tr>
		</tbody>
	</table>

	<table>
		<tbody>
			<tr class="information">
				<td>
					{$REPLACE,
,<br />,{$BUSINESS_ADDRESS*}}
				</td>

				<td>
					{$REPLACE,
,<br />,{TRANS_ADDRESS*}}
				</td>
			</tr>
		</tbody>
	</table>

	<table>
		<thead>
			<tr>
				<th>
					{!IDENTIFIER}
				</th>

				<th>
					{!ITEM_NAME}
				</th>

				<th>
					{!QUANTITY}
				</th>

				<th>
					{!UNIT_PRICE}
				</th>

				<th>
					{!PRICE}
				</th>

				<th>
					{$TAX_LABEL}
				</th>
			</tr>
		</thead>

		<tbody>
			{+START,LOOP,ITEMS}
				<tr class="item">
					<td>
						{TYPE_CODE*}
					</td>

					<td>
						{ITEM_NAME*}
					</td>

					<td>
						{QUANTITY*}
					</td>

					<td>
						{$CURRENCY_SYMBOL,{CURRENCY}}{UNIT_PRICE*}
					</td>

					<td>
						{$CURRENCY_SYMBOL,{CURRENCY}}{PRICE*}
					</td>

					<td>
						{$CURRENCY_SYMBOL,{CURRENCY}}{TAX*} ({TAX_RATE*}%)
					</td>
				</tr>
			{+END}
		</tbody>

		<tfoot>
			<tr class="total">
				<td colspan="4"></td>

				<td class="total">
					{$CURRENCY_SYMBOL,{CURRENCY}}{TOTAL_PRICE*}
				</td>

				<td class="total">
					{$CURRENCY_SYMBOL,{CURRENCY}}{TOTAL_TAX*}
				</td>
			</tr>

			<tr class="total">
				<td colspan="6">
					{!GRAND_TOTAL}: {$CURRENCY_SYMBOL,{CURRENCY}}{TOTAL_AMOUNT*}
				</td>
			</tr>
		</tfoot>
	</table>
</div>