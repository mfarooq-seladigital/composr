{TITLE}

<p>
	{!ECOM_PRODUCTS_LOG_TEXT}
</p>

<div class="float-surrounder">
	{CONTENT}

	{+START,IF_NON_EMPTY,{PAGINATION}}
		<div class="pagination-spacing float-surrounder ajax-block-wrapper-links">
			{PAGINATION}
		</div>
	{+END}
</div>
