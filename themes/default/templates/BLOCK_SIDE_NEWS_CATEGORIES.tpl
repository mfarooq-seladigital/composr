<section class="box box---block-side-news-categories"><div class="box-inner">
	<h3>{!JUST_NEWS_CATEGORIES}</h3>

	<ul class="compact-list">
		{+START,LOOP,CATEGORIES}
			<li><a title="{NAME*}: {$STRIP_TAGS,{!CATEGORY_SUBORDINATE_2,{COUNT*}}}" href="{URL*}">{NAME*}</a></li>
		{+END}
	</ul>
</div></section>
