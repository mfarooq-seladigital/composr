<section class="box box---block-main-forum-news"><div class="box-inner">
	{+START,IF_NON_EMPTY,{TITLE}}<h2>{TITLE}</h2>{+END}

	<div class="webstandards-checker-off">
		{CONTENT}
	</div>

	{+START,IF_NON_EMPTY,{ARCHIVE_URL}}
		<ul class="horizontal-links associated-links-block-group force-margin">
			<li><a href="{ARCHIVE_URL*}">{!VIEW_ARCHIVE}</a></li>
		</ul>
	{+END}
</div></section>
