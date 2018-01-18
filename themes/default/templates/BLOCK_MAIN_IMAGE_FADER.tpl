{$REQUIRE_JAVASCRIPT,galleries}

{$SET,RAND_FADER_IMAGE,{$RAND}}

{+START,IF,{$EQ,{BLOCK_ID},small_version}}
	<div class="box box---block-main-image-fader" data-view="BlockMainImageFader" data-view-params="{+START,PARAMS_JSON,RAND_FADER_IMAGE,MILL,TITLES,HTML,IMAGES}{_*}{+END}" data-keep-fix="{TITLES}{HTML}{IMAGES}{MILL}">
		<div class="box-inner">
			<h2>{!MEDIA}</h2>

			<div class="img-thumb-wrap">
				<a href="{GALLERY_URL*}"><img class="img-thumb" id="image_fader_{$GET%,RAND_FADER_IMAGE}" src="{$ENSURE_PROTOCOL_SUITABILITY*,{FIRST_URL}}" alt="" /></a>
			</div>
		</div>
	</div>
{+END}
{+START,IF,{$NEQ,{BLOCK_ID},small_version}}
	<div class="gallery-tease-pic-wrap" data-view="BlockMainImageFader" data-view-params="{+START,PARAMS_JSON,RAND_FADER_IMAGE,MILL,TITLES,HTML,IMAGES}{_*}{+END}" data-keep-fix="{TITLES}{HTML}{IMAGES}{MILL}">
		<div class="gallery-tease-pic">
		<div class="box box---gallery-tease-pic"><div class="box-inner">
			<div class="float-surrounder">
				<div class="gallery-tease-pic-pic">
					<div class="img-thumb-wrap">
						<a href="{GALLERY_URL*}"><img class="img-thumb" id="image_fader_{$GET%,RAND_FADER_IMAGE}" src="{$ENSURE_PROTOCOL_SUITABILITY*,{FIRST_URL}}" alt="" /></a>
					</div>
				</div>

				<h2 id="image_fader_title_{$GET%,RAND_FADER_IMAGE}">{!MEDIA}</h2>

				<div class="gallery-tease-pic-teaser" id="image_fader_scrolling_text_{$GET%,RAND_FADER_IMAGE}">
					<span aria-busy="true"><img alt="" src="{$IMG*,loading}" /></span>
				</div>
			</div>
		</div></div>
	</div>
	</div>
{+END}
