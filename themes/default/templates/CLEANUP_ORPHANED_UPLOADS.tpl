{+START,IF_ARRAY_NON_EMPTY,FOUND}
	<section class="box box---cleanup-orphaned-uploads"><div class="box-inner">
		<h3>{!ORPHANED_UPLOADS}</h3>

		{+START,LOOP,FOUND}
			<p><a href="{URL*}">{PATH*}</a></p>
		{+END}
	</div></section>
{+END}
